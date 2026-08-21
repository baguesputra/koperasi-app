<?php

namespace App\Services\Anggota;

use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResignService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    /**
     * Ringkasan data untuk preview modal konfirmasi.
     */
    public function ringkasan(Anggota $anggota): array
    {
        return $anggota->ringkasanResign();
    }

    /**
     * Eksekusi proses resign dalam 1 DB transaction.
     *
     * Alur (semua via JurnalKasService - atomic + lockForUpdate):
     *   1. Lock anggota + angsuran/percepatan belum_bayar.
     *   2. Hitung snapshot simpanan & total tagihan pelunasan.
     *   3. Validasi simpanan >= tagihan.
     *   4. Loop angsuran belum_bayar:
     *      a. Tandai lunas.
     *      b. Jurnal MASUK ke kantong:pengembalian_simpanan
     *         (sub_judul: "Diambil dari simpanan anggota").
     *   5. Setelah semua angsuran dilunasi, transfer saldo dari
     *      pengembalian_simpanan ke pinjaman via transferAntarKantong().
     *   6. Kembalikan sisa simpanan (jika ada) via jurnal KELUAR
     *      dari kantong:pinjaman (sub_judul: "Pengembalian ke anggota").
     *      Alasan: simpanan anggota sudah "menjadi bagian" dari saldo_pinjaman
     *      (via topup & pencairan), jadi return ditarik dari rekening pinjaman,
     *      bukan dari rekening transit pengembalian_simpanan (yang saldo akhirnya 0).
     *   7. Update anggota: status=resign, snapshot JSON.
     *   8. Sinkron users.status=nonaktif.
     *   9. Audit log.
     */
    public function proses(Anggota $anggota, string $alasan, string $tanggalResign, User $aktor): void
    {
        DB::transaction(function () use ($anggota, $alasan, $tanggalResign, $aktor) {
            $anggotaLocked = Anggota::lockForUpdate()->findOrFail($anggota->id);

            if ($anggotaLocked->status !== 'aktif') {
                throw new RuntimeException(
                    "Anggota {$anggotaLocked->nama} berstatus '{$anggotaLocked->status}', tidak bisa di-resign."
                );
            }

            // Snapshot simpanan & hitung total tagihan.
            $simpananPokok = (float) $anggotaLocked->simpanan()->where('jenis', 'pokok')->sum('jumlah');
            $simpananWajib = (float) $anggotaLocked->simpanan()->where('jenis', 'wajib')->sum('jumlah');
            $danaSosial = (float) $anggotaLocked->simpanan()->where('jenis', 'dana_sosial')->sum('jumlah');
            $totalSimpananKembali = $simpananPokok + $simpananWajib;

            $pinjamanAktif = $anggotaLocked->pinjaman()->where('status', 'aktif')->get();
            $totalTagihan = 0.0;
            foreach ($pinjamanAktif as $p) {
                $totalTagihan += $p->sisaTotalBayarAktif();
            }

            if ($totalSimpananKembali < $totalTagihan) {
                throw new RuntimeException(
                    'Saldo simpanan pokok+wajib (Rp '.number_format($totalSimpananKembali, 0, ',', '.').
                    ') tidak cukup untuk melunasi pinjaman (Rp '.number_format($totalTagihan, 0, ',', '.').
                    '). Selisih Rp '.number_format($totalTagihan - $totalSimpananKembali, 0, ',', '.').
                    ' harus dilunasi via menu Konfirmasi Angsuran terlebih dahulu.'
                );
            }

            $dataLama = [
                'status' => $anggotaLocked->status,
                'simpanan_pokok' => $simpananPokok,
                'simpanan_wajib' => $simpananWajib,
                'dana_sosial' => $danaSosial,
                'sisa_tagihan_pinjaman' => $totalTagihan,
            ];

            // Step 4: Loop pelunasan angsuran (jurnal masuk ke kantong:pengembalian_simpanan).
            $totalPelunasan = 0.0;
            foreach ($pinjamanAktif as $pinjaman) {
                $angsuranList = Angsuran::where('pinjaman_id', $pinjaman->id)
                    ->where('status', 'belum_bayar')
                    ->lockForUpdate()
                    ->orderBy('cicilan_ke')
                    ->get();

                foreach ($angsuranList as $angsuran) {
                    $angsuran->update([
                        'status' => 'lunas',
                        'tanggal_konfirmasi_bayar' => now(),
                        'confirmed_by' => $aktor->id,
                    ]);

                    $this->jurnalKas->catat(
                        tipe: 'masuk',
                        kategori: 'pelunasan_resign_pinjaman',
                        kantong: 'pengembalian_simpanan',
                        jumlah: (float) $angsuran->total_bayar,
                        keterangan: "Pelunasan resign angsuran ke-{$angsuran->cicilan_ke} - {$anggotaLocked->nama}",
                        referensiId: $angsuran->id,
                        tanggal: $tanggalResign,
                        userId: $aktor->id,
                        subJudul: 'Diambil dari simpanan anggota',
                    );

                    $totalPelunasan += (float) $angsuran->total_bayar;
                }

                // Angsuran percepatan dari pengajuan aktif
                $pengajuanAktif = $pinjaman->pengajuanPercepatan()->where('status', 'aktif')->latest()->first();
                if ($pengajuanAktif) {
                    $percepatanList = AngsuranPercepatan::where('pengajuan_percepatan_id', $pengajuanAktif->id)
                        ->where('status', 'belum_bayar')
                        ->lockForUpdate()
                        ->orderBy('cicilan_ke')
                        ->get();

                    foreach ($percepatanList as $ap) {
                        $ap->update([
                            'status' => 'lunas',
                            'tanggal_konfirmasi_bayar' => now(),
                            'confirmed_by' => $aktor->id,
                        ]);

                        $this->jurnalKas->catat(
                            tipe: 'masuk',
                            kategori: 'pelunasan_resign_pinjaman',
                            kantong: 'pengembalian_simpanan',
                            jumlah: (float) $ap->total_bayar,
                            keterangan: "Pelunasan resign angsuran (percepatan) ke-{$ap->cicilan_ke} - {$anggotaLocked->nama}",
                            referensiId: $ap->id,
                            tanggal: $tanggalResign,
                            userId: $aktor->id,
                            subJudul: 'Diambil dari simpanan anggota',
                        );

                        $totalPelunasan += (float) $ap->total_bayar;
                    }
                }

                // Tandai pinjaman lunas kalau semua jadwal sudah tidak ada belum_bayar.
                $sisaLama = Angsuran::where('pinjaman_id', $pinjaman->id)->where('status', 'belum_bayar')->count();
                $sisaBaru = $pengajuanAktif
                    ? AngsuranPercepatan::where('pengajuan_percepatan_id', $pengajuanAktif->id)->where('status', 'belum_bayar')->count()
                    : 0;

                if ($sisaLama === 0 && $sisaBaru === 0) {
                    $pinjaman->update(['status' => 'lunas']);
                }
            }

            // Step 5: Transfer saldo dari pengembalian_simpanan ke pinjaman.
            // Hanya transfer sebesar pelunasan (sisanya akan diretur).
            if ($totalPelunasan > 0) {
                $this->jurnalKas->transferAntarKantong(
                    kantongAsal: 'pengembalian_simpanan',
                    kantongTujuan: 'pinjaman',
                    jumlah: $totalPelunasan,
                    keterangan: "Pelunasan angsuran otomatis saat resign - {$anggotaLocked->nama}",
                    referensiId: $anggotaLocked->id,
                    tanggal: $tanggalResign,
                    userId: $aktor->id,
                    subJudul: 'Pelunasan angsuran otomatis saat resign',
                );
            }

            // Step 6: Kembalikan sisa simpanan ke anggota (skip jika 0).
            $alokasiPokok = min($simpananPokok, $totalPelunasan);
            $alokasiWajib = max(0, $totalPelunasan - $simpananPokok);
            $kembaliPokok = max(0, $simpananPokok - $alokasiPokok);
            $kembaliWajib = max(0, $simpananWajib - $alokasiWajib);

            if ($kembaliPokok > 0) {
                $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'return_simpanan_pokok',
                    kantong: 'pinjaman',
                    jumlah: $kembaliPokok,
                    keterangan: "Pengembalian simpanan pokok resign - {$anggotaLocked->nama}",
                    referensiId: $anggotaLocked->id,
                    tanggal: $tanggalResign,
                    userId: $aktor->id,
                    subJudul: 'Pengembalian ke anggota',
                );
            }

            if ($kembaliWajib > 0) {
                $this->jurnalKas->catat(
                    tipe: 'keluar',
                    kategori: 'return_simpanan_wajib',
                    kantong: 'pinjaman',
                    jumlah: $kembaliWajib,
                    keterangan: "Pengembalian simpanan wajib resign - {$anggotaLocked->nama}",
                    referensiId: $anggotaLocked->id,
                    tanggal: $tanggalResign,
                    userId: $aktor->id,
                    subJudul: 'Pengembalian ke anggota',
                );
            }

            // Snapshot settlement untuk audit & reprint PDF.
            $settlement = [
                'tagihan_pelunasan' => $totalPelunasan,
                'simpanan_pokok_total' => $simpananPokok,
                'simpanan_wajib_total' => $simpananWajib,
                'dana_sosial_hangus' => $danaSosial,
                'alokasi_dari_pokok' => $alokasiPokok,
                'alokasi_dari_wajib' => $alokasiWajib,
                'kembali_pokok' => $kembaliPokok,
                'kembali_wajib' => $kembaliWajib,
                'total_dikembalikan' => $kembaliPokok + $kembaliWajib,
                'tanggal_proses' => $tanggalResign,
                'aktor' => $aktor->no_karyawan ?? $aktor->name,
            ];

            // Update anggota + user.
            $anggotaLocked->update([
                'status' => 'resign',
                'tanggal_resign' => $tanggalResign,
                'alasan_resign' => $alasan,
                'resigned_by' => $aktor->id,
                'resigned_settlement_json' => $settlement,
            ]);

            if ($anggotaLocked->user_id) {
                User::where('id', $anggotaLocked->user_id)->update(['status' => 'nonaktif']);
            }

            AuditLog::catat(
                aksi: 'anggota_resign',
                keterangan: "Resign anggota {$anggotaLocked->nama} ({$anggotaLocked->no_anggota}). ".
                    "Pelunasan: Rp ".number_format($totalPelunasan, 0, ',', '.').
                    ", pengembalian simpanan: Rp ".number_format($kembaliPokok + $kembaliWajib, 0, ',', '.').
                    ", dana_sosial hangus: Rp ".number_format($danaSosial, 0, ',', '.').
                    ". Alasan: {$alasan}",
                dataLama: $dataLama,
                dataBaru: [
                    'status' => 'resign',
                    'tanggal_resign' => $tanggalResign,
                    'alasan_resign' => $alasan,
                    'resigned_by' => $aktor->no_karyawan ?? $aktor->name,
                    'settlement' => $settlement,
                ]
            );
        });
    }
}

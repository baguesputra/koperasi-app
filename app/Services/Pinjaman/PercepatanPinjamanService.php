<?php

namespace App\Services\Pinjaman;

use App\Models\AuditLog;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Services\Wa\WaPesan;
use App\Services\Wa\WaService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PercepatanPinjamanService
{
    public function __construct(private EligibilitasPinjamanService $eligibilitas) {}

    public function ajukan(Pinjaman $pinjaman, string $tipe, ?int $tenorBaru, string $keterangan): PengajuanPercepatan
    {
        if ($pinjaman->status !== 'aktif') {
            throw new RuntimeException('Hanya pinjaman aktif yang bisa diajukan perubahan.');
        }
        if ($pinjaman->sudah_pakai_percepatan) {
            throw new RuntimeException('Pinjaman ini sudah pernah menggunakan hak perubahan tenor/pelunasan dipercepat.');
        }

        $adaPengajuanMenunggu = PengajuanPercepatan::where('pinjaman_id', $pinjaman->id)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->exists();
        if ($adaPengajuanMenunggu) {
            throw new RuntimeException('Sudah ada pengajuan yang masih diproses untuk pinjaman ini.');
        }

        if ($tipe === 'perpanjang') {
            $tenorMaksimal = $this->eligibilitas->tenorMaksimal((float) $pinjaman->nominal);
            if (! $tenorMaksimal || $tenorBaru > $tenorMaksimal) {
                throw new RuntimeException("Tenor maksimal untuk nominal pinjaman ini adalah {$tenorMaksimal} bulan.");
            }
        }

        $pengajuan = PengajuanPercepatan::create([
            'pinjaman_id' => $pinjaman->id,
            'tipe' => $tipe,
            'tenor_lama' => $pinjaman->tenor_bulan,
            'tenor_baru' => in_array($tipe, ['percepat', 'perpanjang']) ? $tenorBaru : null,
            'keterangan' => $keterangan,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        AuditLog::catat(
            aksi: 'percepatan_diajukan',
            keterangan: "Pengajuan perubahan tenor ".self::labelTipe($tipe)." untuk pinjaman #{$pinjaman->id} ({$pinjaman->anggota->nama}) diajukan. Nominal pinjaman: ".WaPesan::rupiah($pinjaman->nominal),
            dataLama: null,
            dataBaru: [
                'pengajuan_id' => $pengajuan->id,
                'pinjaman_id' => $pinjaman->id,
                'tipe' => $tipe,
                'tenor_lama' => $pinjaman->tenor_bulan,
                'tenor_baru' => $tenorBaru,
                'keterangan' => $keterangan,
                'status' => 'diajukan',
            ]
        );

        $labelTipe = self::labelTipe($tipe);

        WaService::keAnggota(
            $pinjaman->anggota,
            'perubahan_tenor_diajukan',
            WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_anggota,
                'Pengajuan perubahan jadwal angsuran Anda telah kami terima pada '.now()->translatedFormat('d F Y')." dengan rincian sebagai berikut:\n\n"
                ."- Nomor Referensi Pinjaman: #{$pinjaman->id}\n"
                .'- Nominal Pinjaman: '.WaPesan::rupiah($pinjaman->nominal)."\n"
                ."- Jenis Pengajuan: {$labelTipe}\n"
                .($pengajuan->tenor_baru ? "- Tenor: {$pengajuan->tenor_lama} bulan menjadi *{$pengajuan->tenor_baru} bulan*\n" : '')
                ."- Keterangan: {$keterangan}\n\n"
                .'Status saat ini: *Menunggu verifikasi Bendahara*.'
                .' Pemberitahuan selanjutnya akan kami sampaikan melalui WhatsApp ini.')
        );

        WaService::kePengurus(
            'perubahan_tenor_diajukan',
            WaPesan::susun(null, null,
                "Notifikasi Pengajuan Perubahan Jadwal Angsuran\n\n"
                ."Telah diterima pengajuan perubahan dengan rincian sebagai berikut:\n\n"
                ."- Pemohon: {$pinjaman->anggota->nama} (No. Anggota: {$pinjaman->anggota->no_anggota})\n"
                ."- Nomor Referensi Pinjaman: #{$pinjaman->id}\n"
                .'- Nominal Pinjaman: '.WaPesan::rupiah($pinjaman->nominal)."\n"
                ."- Jenis Pengajuan: {$labelTipe}\n"
                .($pengajuan->tenor_baru ? "- Tenor: {$pengajuan->tenor_lama} bulan menjadi {$pengajuan->tenor_baru} bulan\n" : '')
                ."- Keterangan: {$keterangan}\n\n"
                .'Mohon lakukan verifikasi melalui sistem koperasi.')
        );

        return $pengajuan;
    }

    /**
     * Preview simulasi jadwal/nominal SEBELUM disubmit, dipakai di form pengajuan.
     */
    public function preview(Pinjaman $pinjaman, string $tipe, ?int $tenorBaru): array
    {
        $sisaPokok = (float) $pinjaman->angsuranBelumBayar()->sum('nominal_pokok');
        $persentase = (float) $pinjaman->persentase_bunga / 100;

        if ($tipe === 'lunas_total') {
            $bunga = round($sisaPokok * $persentase, 2);

            return [
                'sisa_pokok' => $sisaPokok,
                'bunga' => $bunga,
                'total_bayar' => $sisaPokok + $bunga,
            ];
        }

        $pokokPerBulan = round($sisaPokok / $tenorBaru, 2);
        $sisa = $sisaPokok;
        $jadwal = [];

        for ($i = 1; $i <= $tenorBaru; $i++) {
            $bunga = round($sisa * $persentase, 2);
            $jadwal[] = [
                'cicilan_ke' => $i,
                'nominal_pokok' => $pokokPerBulan,
                'nominal_bunga' => $bunga,
                'total_bayar' => $pokokPerBulan + $bunga,
            ];
            $sisa -= $pokokPerBulan;
        }

        return ['sisa_pokok' => $sisaPokok, 'jadwal' => $jadwal];
    }

    public function approveBendahara(PengajuanPercepatan $p, string $catatan): void
    {
        $statusLama = $p->status;
        $p->update(['status' => 'approved_bendahara', 'catatan_bendahara' => $catatan]);

        AuditLog::catat(
            aksi: 'percepatan_setujui_bendahara',
            keterangan: "Pengajuan percepatan #{$p->id} (pinjaman #{$p->pinjaman->id}) disetujui oleh Bendahara. Jenis: ".self::labelTipe($p->tipe),
            dataLama: ['status' => $statusLama],
            dataBaru: ['status' => 'approved_bendahara', 'catatan_bendahara' => $catatan]
        );

        WaService::keAnggota(
            $p->pinjaman->anggota,
            'perubahan_tenor_disetujui_bendahara',
            WaPesan::susun($p->pinjaman->anggota->nama, $p->pinjaman->anggota->no_anggota,
                'Pengajuan '.self::labelTipe($p->tipe)." Anda untuk pinjaman #{$p->pinjaman->id} ("
                .WaPesan::rupiah($p->pinjaman->nominal).') telah *Disetujui Bendahara* dan sedang menunggu persetujuan Ketua.'
                .' Pemberitahuan selanjutnya akan kami sampaikan melalui WhatsApp ini.')
        );
    }

    public function rejectBendahara(PengajuanPercepatan $p, string $catatan): void
    {
        $statusLama = $p->status;
        $p->update(['status' => 'ditolak', 'catatan_bendahara' => $catatan]);

        AuditLog::catat(
            aksi: 'percepatan_tolak_bendahara',
            keterangan: "Pengajuan percepatan #{$p->id} (pinjaman #{$p->pinjaman->id}) ditolak oleh Bendahara. Jenis: ".self::labelTipe($p->tipe),
            dataLama: ['status' => $statusLama],
            dataBaru: ['status' => 'ditolak', 'catatan_bendahara' => $catatan]
        );

        WaService::keAnggota(
            $p->pinjaman->anggota,
            'perubahan_tenor_ditolak',
            self::pesanDitolak('Bendahara', $p, $catatan)
        );
    }

    public function rejectKetua(PengajuanPercepatan $p, string $catatan): void
    {
        $statusLama = $p->status;
        $p->update(['status' => 'ditolak', 'catatan_ketua' => $catatan]);

        AuditLog::catat(
            aksi: 'percepatan_tolak_ketua',
            keterangan: "Pengajuan percepatan #{$p->id} (pinjaman #{$p->pinjaman->id}) ditolak oleh Ketua. Jenis: ".self::labelTipe($p->tipe),
            dataLama: ['status' => $statusLama],
            dataBaru: ['status' => 'ditolak', 'catatan_ketua' => $catatan]
        );

        WaService::keAnggota(
            $p->pinjaman->anggota,
            'perubahan_tenor_ditolak',
            self::pesanDitolak('Ketua', $p, $catatan)
        );
    }

    public function approveKetua(PengajuanPercepatan $pengajuan, string $catatan, string $bulanBerlaku): void
    {
        $statusLama = $pengajuan->status;
        DB::transaction(function () use ($pengajuan, $catatan, $bulanBerlaku) {
            $pinjaman = $pengajuan->pinjaman;

            $semuaBelumBayar = $pinjaman->angsuran()
                ->where('status', 'belum_bayar')
                ->orderBy('cicilan_ke')
                ->lockForUpdate()
                ->get();

            // Lunas Total SELALU ganti semua sisa cicilan (tidak ada pengecualian bulan berjalan)
            if ($pengajuan->tipe === 'lunas_total') {
                $angsuranDigantikan = $semuaBelumBayar;
            } elseif ($bulanBerlaku === 'bulan_depan') {
                $akhirBulanIni = now()->endOfMonth()->toDateString();
                $cicilanBulanIni = $semuaBelumBayar->first(
                    fn ($a) => $a->tanggal_jatuh_tempo->toDateString() === $akhirBulanIni
                );
                $angsuranDigantikan = $cicilanBulanIni
                    ? $semuaBelumBayar->reject(fn ($a) => $a->id === $cicilanBulanIni->id)
                    : $semuaBelumBayar;
            } else {
                $angsuranDigantikan = $semuaBelumBayar;
            }

            $sisaPokok = (float) $angsuranDigantikan->sum('nominal_pokok');

            foreach ($angsuranDigantikan as $angsuran) {
                $angsuran->update(['status' => 'digantikan', 'pengajuan_percepatan_id' => $pengajuan->id]);
            }

            $pengajuan->update([
                'status' => 'aktif',
                'catatan_ketua' => $catatan,
                'bulan_berlaku' => $bulanBerlaku,
                'sisa_pokok_saat_approval' => $sisaPokok,
            ]);

            $tanggalMulai = $bulanBerlaku === 'bulan_ini' ? now() : now()->addMonthNoOverflow();
            $persentase = (float) $pinjaman->persentase_bunga / 100;

            if ($pengajuan->tipe === 'lunas_total') {
                $bunga = round($sisaPokok * $persentase, 2);
                $totalBayar = $sisaPokok + $bunga;

                $pengajuan->update(['nominal_final' => $totalBayar]);

                $pengajuan->angsuranBaru()->create([
                    'cicilan_ke' => 1,
                    'nominal_pokok' => $sisaPokok,
                    'nominal_bunga' => $bunga,
                    'total_bayar' => $totalBayar,
                    'status' => 'belum_bayar',
                    'tanggal_jatuh_tempo' => $tanggalMulai->copy()->endOfMonth(),
                ]);
            } else {
                $tenorBaru = $pengajuan->tenor_baru;
                $pokokPerBulan = round($sisaPokok / $tenorBaru, 2);
                $sisa = $sisaPokok;

                for ($i = 1; $i <= $tenorBaru; $i++) {
                    $bunga = round($sisa * $persentase, 2);

                    // Cicilan terakhir menyerap sisa pembulatan supaya Σpokok == sisa pokok persis
                    $pokok = ($i === $tenorBaru) ? $sisa : $pokokPerBulan;

                    $pengajuan->angsuranBaru()->create([
                        'cicilan_ke' => $i,
                        'nominal_pokok' => $pokok,
                        'nominal_bunga' => $bunga,
                        'total_bayar' => $pokok + $bunga,
                        'status' => 'belum_bayar',
                        'tanggal_jatuh_tempo' => $tanggalMulai->copy()->addMonths($i - 1)->endOfMonth(),
                    ]);

                    $sisa -= $pokok;
                }
            }

            $pinjaman->update(['sudah_pakai_percepatan' => true]);
        });

        AuditLog::catat(
            aksi: 'percepatan_setujui_ketua',
            keterangan: "Pengajuan percepatan #{$pengajuan->id} (pinjaman #{$pengajuan->pinjaman->id}) disetujui oleh Ketua. Jenis: ".self::labelTipe($pengajuan->tipe).", berlaku: {$bulanBerlaku}",
            dataLama: ['status' => $statusLama],
            dataBaru: [
                'status' => 'aktif',
                'catatan_ketua' => $catatan,
                'bulan_berlaku' => $bulanBerlaku,
                'sisa_pokok_saat_approval' => $pengajuan->fresh()->sisa_pokok_saat_approval,
                'nominal_final' => $pengajuan->fresh()->nominal_final,
            ]
        );

        $labelBulan = $bulanBerlaku === 'bulan_ini' ? 'bulan ini ('.now()->translatedFormat('F Y').')' : 'bulan depan ('.now()->addMonthNoOverflow()->translatedFormat('F Y').')';
        $isi = 'Selamat! Pengajuan '.self::labelTipe($pengajuan->tipe)." Anda untuk pinjaman #{$pengajuan->pinjaman->id} ("
            .WaPesan::rupiah($pengajuan->pinjaman->nominal).") telah *DISETUJUI* oleh Ketua dan berlaku mulai {$labelBulan}."
            .($catatan ? "\n\nCatatan: {$catatan}" : '')
            ."\n\nJadwal angsuran terbaru dapat dilihat pada sistem koperasi. Terima kasih.";

        WaService::keAnggota(
            $pengajuan->pinjaman->anggota,
            'perubahan_tenor_disetujui_ketua',
            WaPesan::susun($pengajuan->pinjaman->anggota->nama, $pengajuan->pinjaman->anggota->no_anggota, $isi)
        );
    }

    private static function labelTipe(string $tipe): string
    {
        return match ($tipe) {
            'percepat' => 'Percepatan Pelunasan (tenor diperpendek)',
            'perpanjang' => 'Perpanjangan Tenor',
            'lunas_total' => 'Pelunasan Total',
            default => ucfirst($tipe),
        };
    }

    private static function pesanDitolak(string $oleh, PengajuanPercepatan $p, string $catatan): string
    {
        $isi = 'Mohon maaf, pengajuan '.self::labelTipe($p->tipe)." Anda untuk pinjaman #{$p->pinjaman->id} ("
            .WaPesan::rupiah($p->pinjaman->nominal).") telah *DITOLAK* oleh {$oleh}."
            .($catatan ? "\n\nCatatan: {$catatan}" : '')
            ."\n\nApabila terdapat pertanyaan lebih lanjut, silakan menghubungi pengurus atau Bendahara Koperasi.";

        return WaPesan::susun($p->pinjaman->anggota->nama, $p->pinjaman->anggota->no_anggota, $isi);
    }
}

<?php

namespace App\Services\Pinjaman;

use App\Helpers\TerbilangHelper;
use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\PengajuanLimit;
use App\Models\Pinjaman;
use App\Models\RekeningAnggota;
use App\Models\User;
use App\Services\Wa\WaPesan;
use App\Services\Wa\WaService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengajuanPinjamanService
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
        private PerhitunganBungaService $bunga,
    ) {}

    /**
     * @param  array  $rekening  ['mode' => 'tersimpan'|'baru', 'rekening_id' => ?int,
     *                           'nama_bank' => ?string, 'no_rekening' => ?string, 'atas_nama' => ?string]
     * @param  array  $audit  ['ip' => ?string, 'user_agent' => ?string, 'persetujuan' => bool]
     */
    public function ajukan(User $pengaju, float $nominal, int $tenorBulan, string $keperluan, array $rekening, array $audit = []): Pinjaman
    {
        if (empty($audit['persetujuan']) || $audit['persetujuan'] !== true) {
            throw new RuntimeException('Anda harus menyetujui seluruh syarat & ketentuan pinjaman terlebih dahulu.');
        }

        $anggota = $pengaju->anggota;

        $adaPengajuanLimit = PengajuanLimit::where('anggota_id', $anggota->id)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->exists();

        if ($adaPengajuanLimit) {
            throw new RuntimeException('Pengajuan kenaikan limit sedang diproses.');
        }

        $adaPinjamanBerjalan = Pinjaman::where('anggota_id', $anggota->id)
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua'])
            ->exists();

        if ($adaPinjamanBerjalan) {
            throw new RuntimeException('Pengajuan pinjaman sedang diproses.');
        }

        $cekEligibilitas = $this->eligibilitas->cek($anggota);

        if (! $cekEligibilitas['boleh']) {
            throw new RuntimeException($cekEligibilitas['alasan']);
        }

        $limitTersedia = (float) $cekEligibilitas['limit_tersedia'];
        if ($nominal > $limitTersedia) {
            throw new RuntimeException(
                'Nominal pinjaman melebihi limit tersedia Anda: Rp '.number_format($limitTersedia, 0, ',', '.')
            );
        }

        $plafonMaksimal = $this->eligibilitas->plafonTertinggiKoperasi();
        if ($nominal > $plafonMaksimal) {
            throw new RuntimeException('Nominal pinjaman melebihi batas kebijakan koperasi.');
        }

        $tenorMaksimal = $this->eligibilitas->tenorMaksimal($nominal);
        if (! $tenorMaksimal) {
            throw new RuntimeException('Nominal pinjaman tidak sesuai dengan ketentuan yang berlaku.');
        }
        if ($tenorBulan > $tenorMaksimal) {
            throw new RuntimeException("Tenor maksimal untuk nominal ini adalah {$tenorMaksimal} bulan.");
        }

        // Tentukan status awal berdasar peran pengaju (pengajuan mandiri pengurus).
        [$statusAwal, $cairOlehBendahara, $catatanBendahara, $catatanAdmin] = $this->statusAwalPengajuanMandiri($pengaju);

        // Tandai privilege reloan dipakai setiap ada angsuran berjalan (1x per siklus).
        // Flag ini dipakai aturan eligibilitas saat kuncian terbuka (sisa <= 1).
        // Jika sudah ada pinjaman aktif lain, update flag-nya juga.
        $pakaiPrivilegeReloan = $cekEligibilitas['sisa_angsuran'] > 0;

        $pinjaman = DB::transaction(function () use ($pengaju, $anggota, $nominal, $tenorBulan, $keperluan, $rekening, $statusAwal, $cairOlehBendahara, $catatanBendahara, $catatanAdmin, $pakaiPrivilegeReloan, $audit) {
            if ($pakaiPrivilegeReloan) {
                Pinjaman::where('anggota_id', $anggota->id)
                    ->where('status', 'aktif')
                    ->update(['sudah_pakai_privilege_reloan' => true]);
            }

            $dataRekening = $this->siapkanRekening($anggota, $rekening);

            $baru = Pinjaman::create([
                'anggota_id' => $anggota->id,
                'pengaju_user_id' => $pengaju->id,
                'nominal' => $nominal,
                'tenor_bulan' => $tenorBulan,
                'nominal_diminta' => $nominal,
                'tenor_diminta' => $tenorBulan,
                'keperluan' => $keperluan,
                'snapshot_bank' => $dataRekening['nama_bank'],
                'snapshot_no_rekening' => $dataRekening['no_rekening'],
                'snapshot_atas_nama' => $dataRekening['atas_nama'],
                'persentase_bunga' => $this->bunga->persentaseBungaBerlaku(),
                'status' => $statusAwal,
                'cair_oleh_bendahara' => $cairOlehBendahara,
                'catatan_admin' => $catatanAdmin,
                'catatan_bendahara' => $catatanBendahara,
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now(),
                'disetujui_pada' => now(),
                'versi_syarat' => config('syarat_pinjaman.versi'),
                'ip_address_setuju' => $audit['ip'] ?? null,
                'user_agent_setuju' => isset($audit['user_agent']) ? substr($audit['user_agent'], 0, 255) : null,
            ]);

            AuditLog::catat(
                aksi: 'pinjaman_diajukan',
                keterangan: "Pengajuan pinjaman baru #{$baru->id} untuk {$anggota->nama} ({$anggota->no_karyawan}) diajukan. Nominal: ".WaPesan::rupiah($nominal).", Tenor: {$tenorBulan} bulan, Keperluan: {$keperluan}",
                dataLama: null,
                dataBaru: [
                    'pinjaman_id' => $baru->id,
                    'anggota_id' => $anggota->id,
                    'nominal' => $nominal,
                    'tenor_bulan' => $tenorBulan,
                    'keperluan' => $keperluan,
                    'status' => $baru->status,
                    'bank' => $baru->snapshot_bank,
                    'no_rekening' => $baru->snapshot_no_rekening,
                    'atas_nama' => $baru->snapshot_atas_nama,
                ],
                userId: $pengaju->id
            );

            return $baru;
        });

        $rincian = "- Nomor Referensi: #{$pinjaman->id}\n"
            .'- Nominal: '.WaPesan::rupiah($nominal).' (terbilang: '.TerbilangHelper::angkaKeTerbilang($nominal)." rupiah)\n"
            ."- Tenor: {$tenorBulan} bulan\n"
            .'- Bunga: '.$pinjaman->persentase_bunga."% per bulan (metode menurun)\n"
            ."- Keperluan: {$keperluan}\n"
            .'- Rekening pencairan: '.$pinjaman->snapshot_bank.' '.$pinjaman->snapshot_no_rekening.' a.n. '.$pinjaman->snapshot_atas_nama;

        WaService::keAnggota(
            $anggota,
            'pinjaman_diajukan',
            WaPesan::susun($anggota->nama, $anggota->no_karyawan,
                'Pengajuan pinjaman Anda telah kami terima pada '.now()->translatedFormat('d F Y')." dengan rincian sebagai berikut:\n\n{$rincian}\n\n"
                .'Status saat ini: *Menunggu verifikasi Sekretaris*.'
                .' Pemberitahuan selanjutnya akan kami sampaikan melalui WhatsApp ini.')
        );

        WaService::kePengurus(
            'pinjaman_diajukan',
            WaPesan::susun(null, null,
                "Notifikasi Pengajuan Pinjaman\n\n"
                ."Telah diterima pengajuan pinjaman baru dengan rincian sebagai berikut:\n\n"
                ."- Pemohon: {$anggota->nama} (No. Karyawan: {$anggota->no_karyawan})\n"
                ."- Nomor Referensi: #{$pinjaman->id}\n"
                .'- Nominal: '.WaPesan::rupiah($nominal)."\n"
                ."- Tenor: {$tenorBulan} bulan\n"
                ."- Keperluan: {$keperluan}\n\n"
                .'Mohon lakukan verifikasi melalui sistem koperasi.')
        );

        return $pinjaman;
    }

    /**
     * Perbarui pengajuan pinjaman sebelum disetujui final Ketua.
     * Jika status sudah melewati tahap awal pengajuan (mis. approved_admin / approved_bendahara),
     * status otomatis di-reset kembali ke tahap awal agar diperiksa ulang dari awal secara sah.
     */
    public function ubah(
        Pinjaman $pinjaman,
        User $pengaju,
        float $nominal,
        int $tenorBulan,
        string $keperluan,
        array $rekening,
        array $audit = [],
    ): Pinjaman {
        if (empty($audit['persetujuan']) || $audit['persetujuan'] !== true) {
            throw new RuntimeException('Anda harus menyetujui seluruh syarat & ketentuan pinjaman terlebih dahulu.');
        }

        $anggota = $pengaju->anggota;
        if (! $anggota || (int) $pinjaman->anggota_id !== (int) $anggota->id) {
            throw new RuntimeException('Anda tidak berhak mengubah pengajuan pinjaman ini.');
        }

        if (! $pinjaman->bisaDieditOlehPemohon()) {
            throw new RuntimeException('Pengajuan pinjaman yang sudah disetujui Ketua atau selesai tidak dapat diubah lagi.');
        }

        $adaPengajuanLimit = PengajuanLimit::where('anggota_id', $anggota->id)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->exists();

        if ($adaPengajuanLimit) {
            throw new RuntimeException('Pengajuan kenaikan limit sedang diproses.');
        }

        $cekEligibilitas = $this->eligibilitas->cek($anggota, sedangDiedit: $pinjaman);
        if (! $cekEligibilitas['boleh']) {
            throw new RuntimeException($cekEligibilitas['alasan']);
        }

        $limitTersedia = (float) $cekEligibilitas['limit_tersedia'];
        if ($nominal > $limitTersedia) {
            throw new RuntimeException(
                'Nominal pinjaman melebihi limit tersedia Anda: Rp '.number_format($limitTersedia, 0, ',', '.')
            );
        }

        $plafonMaksimal = $this->eligibilitas->plafonTertinggiKoperasi();
        if ($nominal > $plafonMaksimal) {
            throw new RuntimeException('Nominal pinjaman melebihi batas kebijakan koperasi.');
        }

        $tenorMaksimal = $this->eligibilitas->tenorMaksimal($nominal);
        if (! $tenorMaksimal) {
            throw new RuntimeException('Nominal pinjaman tidak sesuai dengan ketentuan yang berlaku.');
        }
        if ($tenorBulan > $tenorMaksimal) {
            throw new RuntimeException("Tenor maksimal untuk nominal ini adalah {$tenorMaksimal} bulan.");
        }

        [$statusAwal, $cairOlehBendahara, $catatanBendaharaAwal, $catatanAdminAwal] = $this->statusAwalPengajuanMandiri($pengaju);

        return DB::transaction(function () use (
            $pinjaman,
            $pengaju,
            $anggota,
            $nominal,
            $tenorBulan,
            $keperluan,
            $rekening,
            $statusAwal,
            $cairOlehBendahara,
            $catatanBendaharaAwal,
            $catatanAdminAwal,
            $audit
        ) {
            $locked = Pinjaman::lockForUpdate()->findOrFail($pinjaman->id);
            if (! $locked->bisaDieditOlehPemohon()) {
                throw new RuntimeException('Status pengajuan sudah berubah dan tidak dapat diedit lagi.');
            }

            $dataLama = [
                'nominal' => (float) $locked->nominal,
                'tenor_bulan' => (int) $locked->tenor_bulan,
                'keperluan' => $locked->keperluan,
                'status' => $locked->status,
                'bank' => $locked->snapshot_bank,
                'no_rekening' => $locked->snapshot_no_rekening,
            ];

            $diReset = $locked->status !== $statusAwal;
            $dataRekening = $this->siapkanRekening($anggota, $rekening);
            $jumlahEditBaru = ((int) $locked->jumlah_edit) + 1;

            $locked->update([
                'nominal' => $nominal,
                'tenor_bulan' => $tenorBulan,
                'nominal_diminta' => $nominal,
                'tenor_diminta' => $tenorBulan,
                'nominal_disetujui_bendahara' => null,
                'tenor_disetujui_bendahara' => null,
                'kas_saldo_bendahara' => null,
                'keperluan' => $keperluan,
                'snapshot_bank' => $dataRekening['nama_bank'],
                'snapshot_no_rekening' => $dataRekening['no_rekening'],
                'snapshot_atas_nama' => $dataRekening['atas_nama'],
                'persentase_bunga' => $this->bunga->persentaseBungaBerlaku(),
                'status' => $statusAwal,
                'cair_oleh_bendahara' => $cairOlehBendahara,
                'catatan_admin' => $catatanAdminAwal,
                'catatan_bendahara' => $catatanBendaharaAwal,
                'catatan_ketua' => null,
                'diedit_pada' => now(),
                'jumlah_edit' => $jumlahEditBaru,
                'versi_syarat' => config('syarat_pinjaman.versi'),
                'ip_address_setuju' => $audit['ip'] ?? $locked->ip_address_setuju,
                'user_agent_setuju' => isset($audit['user_agent']) ? substr($audit['user_agent'], 0, 255) : $locked->user_agent_setuju,
            ]);

            AuditLog::catat(
                aksi: 'pinjaman_diedit',
                keterangan: "Pengajuan pinjaman #{$locked->id} ({$anggota->nama}) diubah oleh pemohon (revisi ke-{$jumlahEditBaru}). Nominal: ".WaPesan::rupiah($dataLama['nominal']).' -> '.WaPesan::rupiah($nominal).", Tenor: {$dataLama['tenor_bulan']} -> {$tenorBulan} bln".($diReset ? " (verifikasi direset dari {$dataLama['status']} ke {$statusAwal})" : ''),
                dataLama: $dataLama,
                dataBaru: [
                    'nominal' => $nominal,
                    'tenor_bulan' => $tenorBulan,
                    'keperluan' => $keperluan,
                    'status' => $locked->status,
                    'bank' => $locked->snapshot_bank,
                    'no_rekening' => $locked->snapshot_no_rekening,
                    'reset_verifikasi' => $diReset,
                    'jumlah_edit' => $jumlahEditBaru,
                ],
                userId: $pengaju->id,
            );

            return $locked->fresh();
        });
    }

    /**
     * Batalkan pengajuan pinjaman secara mandiri oleh pemohon sebelum disetujui Ketua.
     */
    public function batalkanOlehPemohon(Pinjaman $pinjaman, User $pemohon, ?string $alasan = null): Pinjaman
    {
        $anggota = $pemohon->anggota;
        if (! $anggota || (int) $pinjaman->anggota_id !== (int) $anggota->id) {
            throw new RuntimeException('Anda tidak berhak membatalkan pengajuan pinjaman ini.');
        }

        $alasanBersih = $alasan !== null && trim($alasan) !== ''
            ? trim($alasan)
            : 'Dibatalkan oleh pemohon';

        $hasil = DB::transaction(function () use ($pinjaman, $pemohon, $anggota, $alasanBersih) {
            $locked = Pinjaman::lockForUpdate()->findOrFail($pinjaman->id);
            if (! $locked->bisaDibatalkanOlehPemohon()) {
                throw new RuntimeException('Pengajuan yang sudah disetujui Ketua atau selesai tidak dapat dibatalkan mandiri. Silakan hubungi Bendahara.');
            }

            $statusLama = $locked->status;

            $locked->update([
                'status' => 'dibatalkan',
                'dibatalkan_pada' => now(),
                'dibatalkan_oleh' => $pemohon->id,
                'alasan_batal' => $alasanBersih,
            ]);

            $this->pulihkanPrivilegeReloanBilaPerlu($anggota, $locked->id);

            AuditLog::catat(
                aksi: 'pinjaman_dibatalkan_pemohon',
                keterangan: "Pengajuan pinjaman #{$locked->id} ({$anggota->nama}) dibatalkan oleh pemohon pada status {$statusLama}. Nominal: ".WaPesan::rupiah((float) $locked->nominal).". Alasan: {$alasanBersih}",
                dataLama: ['status' => $statusLama],
                dataBaru: [
                    'status' => 'dibatalkan',
                    'dibatalkan_oleh' => $pemohon->id,
                    'alasan_batal' => $alasanBersih,
                ],
                userId: $pemohon->id,
            );

            return $locked->fresh();
        });

        $isiWa = "Dengan hormat,\n\nPengajuan pinjaman Anda dengan rincian berikut:\n\n"
            ."- Nomor Referensi: #{$hasil->id}\n"
            .'- Nominal: '.WaPesan::rupiah((float) $hasil->nominal)."\n"
            ."- Tenor: {$hasil->tenor_bulan} bulan\n\n"
            ."telah berhasil *DIBATALKAN* atas permintaan Anda.\n"
            ."Alasan: {$alasanBersih}\n\n"
            .'Anda dapat mengajukan pinjaman baru kapan saja melalui Portal Anggota apabila memenuhi ketentuan.';

        WaService::keAnggota(
            $anggota,
            'pinjaman_dibatalkan',
            WaPesan::susun($anggota->nama, $anggota->no_karyawan, $isiWa)
        );

        return $hasil;
    }

    /**
     * Pulihkan flag `sudah_pakai_privilege_reloan` pada pinjaman aktif jika
     * pengajuan reloan yang mengaktifkannya dibatalkan atau ditolak.
     */
    public function pulihkanPrivilegeReloanBilaPerlu(?Anggota $anggota, int $abaikanPinjamanId): void
    {
        if (! $anggota) {
            return;
        }

        $aktifList = Pinjaman::where('anggota_id', $anggota->id)
            ->where('status', 'aktif')
            ->orderByDesc('id')
            ->get();

        if ($aktifList->count() !== 1) {
            return;
        }

        $aktif = $aktifList->first();
        if (! $aktif || ! $aktif->sudah_pakai_privilege_reloan || $abaikanPinjamanId <= $aktif->id) {
            return;
        }

        $adaPengajuanLain = Pinjaman::where('anggota_id', $anggota->id)
            ->where('id', '!=', $abaikanPinjamanId)
            ->where('id', '>', $aktif->id)
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua', 'aktif', 'lunas'])
            ->exists();

        if (! $adaPengajuanLain) {
            $aktif->update(['sudah_pakai_privilege_reloan' => false]);
        }
    }

    /**
     * Status awal saat pengurus mengajukan pinjaman untuk dirinya sendiri.
     * - Sekretaris: langsung approved_admin agar tidak memverifikasi dirinya sendiri,
     *   diteruskan ke Bendahara -> Ketua -> Pencairan Bendahara.
     * - Bendahara: lewat Sekretaris dulu lalu tahap Bendahara dilewati otomatis
     *   saat Sekretaris meneruskan (agar tak self-approve), tinggal menunggu Ketua.
     * - Ketua: perlu pemeriksaan & pencairan Bendahara (bukan self-approve).
     * - Lainnya (anggota / pengawas): alur normal admin → bendahara → ketua.
     */
    public function statusAwalPengajuanMandiri(User $pengaju): array
    {
        if ($pengaju->hasRole('sekretaris') && ! $pengaju->hasAnyRole(['bendahara', 'ketua_koperasi'])) {
            return ['approved_admin', false, null, 'Diajukan mandiri oleh Sekretaris'];
        }

        if ($pengaju->hasRole('bendahara')) {
            return ['diajukan', false, 'Diajukan mandiri oleh Bendahara', null];
        }

        if ($pengaju->hasRole('ketua_koperasi')) {
            return ['diajukan', true, null, null];
        }

        return ['diajukan', false, null, null];
    }

    /**
     * Ambil data rekening untuk snapshot. Kalau mode "baru", sekalian simpan
     * ke rekening_anggota supaya bisa dipakai lagi nanti.
     */
    private function siapkanRekening(Anggota $anggota, array $rekening): array
    {
        if ($rekening['mode'] === 'tersimpan') {
            $r = RekeningAnggota::where('anggota_id', $anggota->id)
                ->findOrFail($rekening['rekening_id']);

            return [
                'nama_bank' => $r->nama_bank,
                'no_rekening' => $r->no_rekening,
                'atas_nama' => $r->atas_nama,
            ];
        }

        // Mode "baru" - simpan ke rekening_anggota supaya tersedia untuk pengajuan berikutnya
        $jumlahRekening = RekeningAnggota::where('anggota_id', $anggota->id)->count();

        $rekeningBaru = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => $rekening['nama_bank'],
            'no_rekening' => $rekening['no_rekening'],
            'atas_nama' => $rekening['atas_nama'],
            'is_default' => $jumlahRekening === 0, // rekening pertama otomatis jadi default
        ]);

        return [
            'nama_bank' => $rekeningBaru->nama_bank,
            'no_rekening' => $rekeningBaru->no_rekening,
            'atas_nama' => $rekeningBaru->atas_nama,
        ];
    }

    public function preview(float $nominal, int $tenorBulan): array
    {
        $persentase = $this->bunga->persentaseBungaBerlaku();

        return $this->bunga->buatJadwal($nominal, $tenorBulan, $persentase);
    }
}

<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\PengajuanLimit;
use App\Services\Wa\WaPesan;
use App\Services\Wa\WaService;
use RuntimeException;

class PengajuanLimitService
{
    public function __construct(private EligibilitasPinjamanService $eligibilitas) {}

    public function ajukan(Anggota $anggota, float $limitDiminta, string $keterangan): PengajuanLimit
    {
        $adaPengajuanMenunggu = PengajuanLimit::where('anggota_id', $anggota->id)
            ->where('status', 'diajukan')
            ->exists();

        if ($adaPengajuanMenunggu) {
            throw new RuntimeException('Anda masih memiliki pengajuan limit yang belum diproses.');
        }

        $limitSaatIni = $this->eligibilitas->limitMaksimal($anggota);

        if ($limitDiminta <= $limitSaatIni) {
            throw new RuntimeException('Limit yang diajukan harus lebih besar dari limit Anda saat ini: Rp '.number_format($limitSaatIni, 0, ',', '.'));
        }

        $pengajuan = PengajuanLimit::create([
            'anggota_id' => $anggota->id,
            'limit_saat_ini' => $limitSaatIni,
            'limit_diminta' => $limitDiminta,
            'keterangan' => $keterangan,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        WaService::keAnggota(
            $anggota,
            'limit_diajukan',
            WaPesan::susun($anggota->nama, $anggota->no_anggota,
                'Pengajuan kenaikan limit pinjaman Anda telah kami terima pada '.now()->translatedFormat('d F Y')." dengan rincian sebagai berikut:\n\n"
                .'- Limit saat ini: '.WaPesan::rupiah($limitSaatIni)."\n"
                .'- Limit diajukan: '.WaPesan::rupiah($limitDiminta)."\n"
                ."- Keterangan: {$keterangan}\n\n"
                .'Status saat ini: *Menunggu persetujuan Ketua*.'
                .' Pemberitahuan selanjutnya akan kami sampaikan melalui WhatsApp ini.')
        );

        WaService::kePengurus(
            'limit_diajukan',
            WaPesan::susun(null, null,
                "Notifikasi Pengajuan Kenaikan Limit\n\n"
                ."Telah diterima pengajuan kenaikan limit pinjaman dengan rincian sebagai berikut:\n\n"
                ."- Pemohon: {$anggota->nama} (No. Anggota: {$anggota->no_anggota})\n"
                .'- Limit saat ini: '.WaPesan::rupiah($limitSaatIni)."\n"
                .'- Limit diajukan: '.WaPesan::rupiah($limitDiminta)."\n"
                ."- Keterangan: {$keterangan}\n\n"
                .'Mohon lakukan peninjauan melalui sistem koperasi.')
        );

        return $pengajuan;
    }

    public function setujui(PengajuanLimit $pengajuan, string $catatan): void
    {
        $pengajuan->update([
            'status' => 'disetujui',
            'catatan_ketua' => $catatan,
        ]);

        $pengajuan->anggota->update(['limit_custom' => $pengajuan->limit_diminta]);

        AuditLog::catat(
            'setujui_pengajuan_limit',
            "Limit khusus {$pengajuan->anggota->nama} disetujui menjadi Rp ".number_format($pengajuan->limit_diminta, 0, ',', '.'),
            ['limit_custom' => $pengajuan->anggota->limit_custom],
            ['limit_custom' => $pengajuan->limit_diminta]
        );

        WaService::keAnggota(
            $pengajuan->anggota,
            'limit_disetujui',
            WaPesan::susun($pengajuan->anggota->nama, $pengajuan->anggota->no_anggota,
                "Selamat! Pengajuan kenaikan limit pinjaman Anda telah *DISETUJUI* oleh Ketua.\n\n"
                .'- Limit sebelumnya: '.WaPesan::rupiah($pengajuan->limit_saat_ini)."\n"
                .'- Limit berlaku saat ini: '.WaPesan::rupiah($pengajuan->limit_diminta)."\n\n"
                .'Anda kini dapat mengajukan pinjaman hingga batas limit yang berlaku. Terima kasih atas kepercayaan Anda.')
        );
    }

    public function tolak(PengajuanLimit $pengajuan, string $catatan): void
    {
        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);

        $isi = 'Mohon maaf, pengajuan kenaikan limit pinjaman Anda dari '
            .WaPesan::rupiah($pengajuan->limit_saat_ini).' menjadi '.WaPesan::rupiah($pengajuan->limit_diminta)
            .' telah *DITOLAK* oleh Ketua.'
            .($catatan ? "\n\nCatatan: {$catatan}" : '')
            ."\n\nApabila terdapat pertanyaan lebih lanjut, silakan menghubungi pengurus atau Bendahara Koperasi.";

        WaService::keAnggota(
            $pengajuan->anggota,
            'limit_ditolak',
            WaPesan::susun($pengajuan->anggota->nama, $pengajuan->anggota->no_anggota, $isi)
        );
    }
}

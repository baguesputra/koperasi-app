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
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
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

        AuditLog::catat(
            aksi: 'limit_diajukan',
            keterangan: "Pengajuan kenaikan limit untuk {$anggota->nama} ({$anggota->no_karyawan}) diajukan. Limit saat ini: ".WaPesan::rupiah($limitSaatIni).', diminta: '.WaPesan::rupiah($limitDiminta),
            dataLama: null,
            dataBaru: [
                'pengajuan_id' => $pengajuan->id,
                'anggota_id' => $anggota->id,
                'limit_saat_ini' => $limitSaatIni,
                'limit_diminta' => $limitDiminta,
                'keterangan' => $keterangan,
                'status' => 'diajukan',
            ]
        );

        WaService::keAnggota(
            $anggota,
            'limit_diajukan',
            WaPesan::susun($anggota->nama, $anggota->no_karyawan,
                'Pengajuan kenaikan limit pinjaman Anda telah kami terima pada '.now()->translatedFormat('d F Y')." dengan rincian sebagai berikut:\n\n"
                .'- Limit saat ini: '.WaPesan::rupiah($limitSaatIni)."\n"
                .'- Limit diajukan: '.WaPesan::rupiah($limitDiminta)."\n"
                ."- Keterangan: {$keterangan}\n\n"
                .'Status saat ini: *Menunggu verifikasi Bendahara*.'
                .' Pemberitahuan selanjutnya akan kami sampaikan melalui WhatsApp ini.')
        );

        WaService::kePengurus(
            'limit_diajukan',
            WaPesan::susun(null, null,
                "Notifikasi Pengajuan Kenaikan Limit\n\n"
                ."Telah diterima pengajuan kenaikan limit pinjaman dengan rincian sebagai berikut:\n\n"
                ."- Pemohon: {$anggota->nama} (No. Karyawan: {$anggota->no_karyawan})\n"
                .'- Limit saat ini: '.WaPesan::rupiah($limitSaatIni)."\n"
                .'- Limit diajukan: '.WaPesan::rupiah($limitDiminta)."\n"
                ."- Keterangan: {$keterangan}\n\n"
                .'Mohon lakukan verifikasi melalui sistem koperasi.')
        );

        return $pengajuan;
    }

    public function approveBendahara(PengajuanLimit $pengajuan, float $nominal, string $catatan): void
    {
        if ($pengajuan->status !== 'diajukan') {
            throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat diverifikasi Bendahara.');
        }

        if ($nominal <= (float) $pengajuan->limit_saat_ini) {
            throw new RuntimeException('Nominal total limit disetujui harus lebih besar dari limit saat ini (Rp '.number_format($pengajuan->limit_saat_ini, 0, ',', '.').').');
        }

        $pengajuan->update([
            'status' => 'approved_bendahara',
            'limit_disetujui_bendahara' => $nominal,
            'catatan_bendahara' => $catatan,
        ]);

        AuditLog::catat(
            'limit_setujui_bendahara',
            "Pengajuan limit #{$pengajuan->id} ({$pengajuan->anggota->nama}) disetujui Bendahara sebesar ".WaPesan::rupiah($nominal).' dari diminta '.WaPesan::rupiah($pengajuan->limit_diminta),
            ['status' => 'diajukan'],
            ['status' => 'approved_bendahara', 'limit_disetujui_bendahara' => $nominal, 'catatan_bendahara' => $catatan]
        );

        WaService::keAnggota(
            $pengajuan->anggota,
            'limit_disetujui_bendahara',
            WaPesan::susun($pengajuan->anggota->nama, $pengajuan->anggota->no_karyawan,
                'Pengajuan kenaikan limit Anda telah *Disetujui Bendahara* sebesar '.WaPesan::rupiah($nominal)
                .' (diminta: '.WaPesan::rupiah($pengajuan->limit_diminta).') dan sedang menunggu keputusan final Ketua.'
                .' Pemberitahuan selanjutnya akan kami sampaikan melalui WhatsApp ini.')
        );
    }

    public function rejectBendahara(PengajuanLimit $pengajuan, string $catatan): void
    {
        if ($pengajuan->status !== 'diajukan') {
            throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat ditolak Bendahara.');
        }

        $pengajuan->update(['status' => 'ditolak', 'catatan_bendahara' => $catatan]);

        AuditLog::catat(
            aksi: 'limit_tolak_bendahara',
            keterangan: "Pengajuan kenaikan limit untuk {$pengajuan->anggota->nama} ({$pengajuan->anggota->no_karyawan}) ditolak Bendahara. Diminta: ".WaPesan::rupiah($pengajuan->limit_diminta),
            dataLama: ['status' => 'diajukan'],
            dataBaru: ['status' => 'ditolak', 'catatan_bendahara' => $catatan]
        );

        WaService::keAnggota(
            $pengajuan->anggota,
            'limit_ditolak',
            WaPesan::susun($pengajuan->anggota->nama, $pengajuan->anggota->no_karyawan,
                'Mohon maaf, pengajuan kenaikan limit pinjaman Anda sebesar '.WaPesan::rupiah($pengajuan->limit_diminta)
                ." telah *DITOLAK* oleh Bendahara.\n\nCatatan: {$catatan}\n\nApabila terdapat pertanyaan lebih lanjut, silakan menghubungi pengurus Koperasi.")
        );
    }

    public function approveKetua(PengajuanLimit $pengajuan, float $nominal, string $catatan): void
    {
        if ($pengajuan->status !== 'approved_bendahara') {
            throw new RuntimeException('Hanya pengajuan berstatus Disetujui Bendahara yang dapat disetujui Ketua.');
        }

        if ($nominal <= (float) $pengajuan->limit_saat_ini) {
            throw new RuntimeException('Nominal total limit disetujui harus lebih besar dari limit saat ini (Rp '.number_format($pengajuan->limit_saat_ini, 0, ',', '.').').');
        }

        $limitLama = $pengajuan->anggota->limit_custom;
        $pengajuan->update([
            'status' => 'disetujui',
            'limit_disetujui' => $nominal,
            'catatan_ketua' => $catatan,
        ]);

        $pengajuan->anggota->update(['limit_custom' => $nominal]);

        AuditLog::catat(
            'setujui_pengajuan_limit',
            "Limit khusus {$pengajuan->anggota->nama} disetujui menjadi Rp ".number_format($nominal, 0, ',', '.').' (diminta: '.WaPesan::rupiah($pengajuan->limit_diminta).', bendahara: '.WaPesan::rupiah($pengajuan->limit_disetujui_bendahara).')',
            ['limit_custom' => $limitLama],
            ['limit_custom' => $nominal]
        );

        WaService::keAnggota(
            $pengajuan->anggota,
            'limit_disetujui',
            WaPesan::susun($pengajuan->anggota->nama, $pengajuan->anggota->no_karyawan,
                "Selamat! Pengajuan kenaikan limit pinjaman Anda telah *DISETUJUI* oleh Ketua.\n\n"
                .'- Limit sebelumnya: '.WaPesan::rupiah($pengajuan->limit_saat_ini)."\n"
                .'- Diminta: '.WaPesan::rupiah($pengajuan->limit_diminta)."\n"
                .'- Disetujui Bendahara: '.WaPesan::rupiah($pengajuan->limit_disetujui_bendahara)."\n"
                .'- Limit berlaku saat ini: '.WaPesan::rupiah($nominal)."\n\n"
                .'Anda kini dapat mengajukan pinjaman hingga batas limit yang berlaku. Terima kasih atas kepercayaan Anda.')
        );
    }

    public function rejectKetua(PengajuanLimit $pengajuan, string $catatan): void
    {
        if ($pengajuan->status !== 'approved_bendahara') {
            throw new RuntimeException('Hanya pengajuan berstatus Disetujui Bendahara yang dapat ditolak Ketua.');
        }

        $pengajuan->update(['status' => 'ditolak', 'catatan_ketua' => $catatan]);

        AuditLog::catat(
            aksi: 'limit_ditolak',
            keterangan: "Pengajuan kenaikan limit untuk {$pengajuan->anggota->nama} ({$pengajuan->anggota->no_karyawan}) ditolak Ketua. Diminta: ".WaPesan::rupiah($pengajuan->limit_diminta).', bendahara: '.WaPesan::rupiah($pengajuan->limit_disetujui_bendahara),
            dataLama: ['status' => 'approved_bendahara'],
            dataBaru: ['status' => 'ditolak', 'catatan_ketua' => $catatan]
        );

        WaService::keAnggota(
            $pengajuan->anggota,
            'limit_ditolak',
            WaPesan::susun($pengajuan->anggota->nama, $pengajuan->anggota->no_karyawan,
                'Mohon maaf, pengajuan kenaikan limit pinjaman Anda dari '
                .WaPesan::rupiah($pengajuan->limit_saat_ini).' (diminta: '.WaPesan::rupiah($pengajuan->limit_diminta)
                .', bendahara: '.WaPesan::rupiah($pengajuan->limit_disetujui_bendahara)
                ." telah *DITOLAK* oleh Ketua.\n\nCatatan: {$catatan}"
                ."\n\nApabila terdapat pertanyaan lebih lanjut, silakan menghubungi pengurus atau Bendahara Koperasi.")
        );
    }

    // ponytail: alias lama agar route/test lama tetap jalan selama transisi
    public function setujui(PengajuanLimit $pengajuan, string $catatan, ?float $nominal = null): void
    {
        $this->approveKetua($pengajuan, $nominal ?? (float) $pengajuan->limit_disetujui_bendahara, $catatan);
    }

    public function tolak(PengajuanLimit $pengajuan, string $catatan): void
    {
        $this->rejectKetua($pengajuan, $catatan);
    }
}

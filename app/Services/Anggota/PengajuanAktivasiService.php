<?php

namespace App\Services\Anggota;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\PengajuanAktivasi;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Wa\WaPesan;
use App\Services\Wa\WaService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengajuanAktivasiService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    public function ajukan(Anggota $anggota, bool $dataBenar, bool $setujuSyarat, ?int $aktorId = null): PengajuanAktivasi
    {
        if ($anggota->status === 'aktif') {
            throw new RuntimeException('Keanggotaan Anda sudah aktif.');
        }

        if ($anggota->status === 'resign') {
            throw new RuntimeException('Akun resign tidak dapat mengajukan aktivasi mandiri. Hubungi admin koperasi.');
        }

        $berjalan = PengajuanAktivasi::where('anggota_id', $anggota->id)
            ->where('status', 'diajukan')
            ->exists();

        if ($berjalan) {
            throw new RuntimeException('Pengajuan aktivasi Anda masih diproses. Tunggu keputusan Ketua.');
        }

        if (! $dataBenar || ! $setujuSyarat) {
            throw new RuntimeException('Konfirmasi kebenaran data dan persetujuan syarat wajib dicentang.');
        }

        $versi = config('syarat_aktivasi.versi');

        $pengajuan = PengajuanAktivasi::create([
            'anggota_id' => $anggota->id,
            'status' => 'diajukan',
            'versi_syarat' => $versi,
            'data_benar' => true,
            'setuju_syarat' => true,
            'tanggal_pengajuan' => now(),
        ]);

        AuditLog::catat(
            'aktivasi_diajukan',
            "Pengajuan aktivasi keanggotaan untuk {$anggota->nama} ({$anggota->no_karyawan}) diajukan. Versi syarat: {$versi}",
            ['status' => $anggota->status],
            ['pengajuan_id' => $pengajuan->id, 'status' => 'diajukan', 'versi_syarat' => $versi],
            $aktorId
        );

        WaService::keAnggota(
            $anggota,
            'aktivasi_diajukan',
            WaPesan::susun($anggota->nama, $anggota->no_karyawan,
                'Pengajuan aktivasi keanggotaan Bapak/Ibu telah kami terima pada '.now()->translatedFormat('d F Y').".\n\n"
                .'Status saat ini: *Menunggu persetujuan Ketua Koperasi*.'
                .' Perkembangan selanjutnya akan kami sampaikan melalui WhatsApp ini.')
        );

        WaService::kePengurus(
            'aktivasi_diajukan',
            WaPesan::susun(null, null,
                "Notifikasi Pengajuan Aktivasi Keanggotaan\n\n"
                ."- Pemohon: {$anggota->nama} (No. Karyawan: {$anggota->no_karyawan})\n"
                .'- Versi syarat: '.$versi."\n\n"
                .'Mohon lakukan peninjauan melalui sistem koperasi.')
        );

        return $pengajuan;
    }

    public function setujui(PengajuanAktivasi $pengajuan, string $catatan, ?int $aktorId = null): void
    {
        if ($pengajuan->status !== 'diajukan') {
            throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat disetujui.');
        }

        DB::transaction(function () use ($pengajuan, $catatan, $aktorId) {
            $anggota = Anggota::lockForUpdate()->findOrFail($pengajuan->anggota_id);

            if ($anggota->status === 'aktif') {
                throw new RuntimeException("Anggota {$anggota->nama} sudah aktif.");
            }

            $pengajuan->update(['status' => 'disetujui', 'catatan_ketua' => $catatan]);
            $anggota->update(['status' => 'aktif']);

            if ($anggota->user_id) {
                User::where('id', $anggota->user_id)->update(['status' => 'aktif']);
            }

            $this->catatSimpananPokok($anggota, $aktorId ?? $anggota->user_id);
        });

        $pengajuan->refresh();

        AuditLog::catat(
            'aktivasi_disetujui',
            "Aktivasi keanggotaan {$pengajuan->anggota->nama} ({$pengajuan->anggota->no_karyawan}) disetujui Ketua. Simpanan pokok tercatat otomatis.",
            ['status' => 'diajukan'],
            ['status' => 'disetujui', 'catatan_ketua' => $catatan],
            $aktorId
        );

        WaService::keAnggota(
            $pengajuan->anggota,
            'aktivasi_disetujui',
            WaPesan::susun($pengajuan->anggota->nama, $pengajuan->anggota->no_karyawan,
                "Dengan hormat,\n\nPengajuan aktivasi keanggotaan Bapak/Ibu telah *DISETUJUI* oleh Ketua Koperasi.\n\n"
                .'Simpanan Pokok telah tercatat. Bapak/Ibu kini dapat menggunakan seluruh layanan koperasi, termasuk pengajuan pinjaman. Terima kasih atas kepercayaan Bapak/Ibu.')
        );
    }

    public function tolak(PengajuanAktivasi $pengajuan, string $catatan, ?int $aktorId = null): void
    {
        if ($pengajuan->status !== 'diajukan') {
            throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat ditolak.');
        }

        $pengajuan->update(['status' => 'ditolak', 'catatan_ketua' => $catatan]);

        AuditLog::catat(
            'aktivasi_ditolak',
            "Pengajuan aktivasi keanggotaan untuk {$pengajuan->anggota->nama} ({$pengajuan->anggota->no_karyawan}) ditolak Ketua.",
            ['status' => 'diajukan'],
            ['status' => 'ditolak', 'catatan_ketua' => $catatan],
            $aktorId
        );

        WaService::keAnggota(
            $pengajuan->anggota,
            'aktivasi_ditolak',
            WaPesan::susun($pengajuan->anggota->nama, $pengajuan->anggota->no_karyawan,
                "Dengan hormat,\n\nMohon maaf, pengajuan aktivasi keanggotaan Bapak/Ibu belum dapat disetujui oleh Ketua Koperasi.\n\nCatatan: {$catatan}\n\n"
                .'Bapak/Ibu dapat memperbaiki data dan mengajukan ulang melalui portal. Apabila memerlukan informasi lebih lanjut, silakan menghubungi pengurus Koperasi.')
        );
    }

    private function catatSimpananPokok(Anggota $anggota, ?int $aktorId): void
    {
        $nominalPokok = SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000;

        Simpanan::firstOrCreate(
            ['anggota_id' => $anggota->id, 'jenis' => 'pokok'],
            [
                'jumlah' => $nominalPokok,
                'bulan_periode' => now()->format('Y-m'),
                'tanggal_input' => now(),
                'input_by' => $aktorId,
            ]
        );

        $this->jurnalKas->catat(
            tipe: 'masuk',
            kategori: 'simpanan_pokok_masuk',
            kantong: 'simpanan',
            jumlah: (float) $nominalPokok,
            keterangan: "Simpanan pokok {$anggota->nama} (aktivasi)",
            referensiId: $anggota->id,
            tanggal: now()->format('Y-m-d'),
            userId: $aktorId ?? $anggota->user_id ?? 1,
            subJudul: 'Simpanan pokok masuk',
        );
    }
}

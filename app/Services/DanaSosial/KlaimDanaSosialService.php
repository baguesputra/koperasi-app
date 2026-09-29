<?php

namespace App\Services\DanaSosial;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\KlaimDanaSosial;
use App\Services\Keuangan\PengeluaranService;
use App\Services\Wa\WaPesan;
use App\Services\Wa\WaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class KlaimDanaSosialService
{
    public function __construct(private PengeluaranService $pengeluaran) {}

    public function ajukan(Anggota $anggota, array $data, UploadedFile $foto, ?int $aktorId = null): KlaimDanaSosial
    {
        $berjalan = KlaimDanaSosial::where('anggota_id', $anggota->id)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->exists();

        if ($berjalan) {
            throw new RuntimeException('Pengajuan santunan Bapak/Ibu masih dalam proses. Mohon menunggu keputusan sebelum mengajukan kembali.');
        }

        $this->validasiJenis($data);

        $path = $foto->store('klaim-dana-sosial', 'public');

        $klaim = KlaimDanaSosial::create([
            'anggota_id' => $anggota->id,
            'jenis' => $data['jenis'],
            'sub_tipe' => $data['sub_tipe'] ?? null,
            'hubungan' => $data['hubungan'] ?? null,
            'tanggal_kejadian' => $data['tanggal_kejadian'],
            'lama_hari' => $data['lama_hari'] ?? null,
            'keterangan' => $data['keterangan'],
            'foto_path' => $path,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        AuditLog::catat(
            aksi: 'klaim_diajukan',
            keterangan: "Pengajuan santunan dana sosial ({$this->labelJenis($klaim->jenis)}) untuk {$anggota->nama} ({$anggota->no_karyawan}) diajukan.",
            dataLama: null,
            dataBaru: [
                'klaim_id' => $klaim->id,
                'anggota_id' => $anggota->id,
                'jenis' => $klaim->jenis,
                'status' => 'diajukan',
            ],
            userId: $aktorId,
        );

        WaService::keAnggota(
            $anggota,
            'klaim_diajukan',
            WaPesan::susun($anggota->nama, $anggota->no_karyawan,
                'Dengan hormat, pengajuan santunan dana sosial Bapak/Ibu ('.$this->labelJenis($klaim->jenis).') telah kami terima pada '.now()->translatedFormat('d F Y').".\n\n"
                .'Status saat ini: *Menunggu verifikasi Bendahara*.'
                .' Perkembangan selanjutnya akan kami sampaikan melalui WhatsApp ini.')
        );

        WaService::kePengurus(
            'klaim_diajukan',
            WaPesan::susun(null, null,
                "Notifikasi Pengajuan Santunan Dana Sosial\n\n"
                ."- Pemohon: {$anggota->nama} (No. Karyawan: {$anggota->no_karyawan})\n"
                .'- Jenis: '.$this->labelJenis($klaim->jenis)."\n\n"
                .'Mohon lakukan verifikasi melalui sistem koperasi.')
        );

        return $klaim;
    }

    public function approveBendahara(KlaimDanaSosial $klaim, float $nominal, string $catatan, ?int $aktorId = null): void
    {
        if ($klaim->status !== 'diajukan') {
            throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat diverifikasi Bendahara.');
        }

        $klaim->update([
            'status' => 'approved_bendahara',
            'nominal_bendahara' => $nominal,
            'catatan_bendahara' => $catatan,
        ]);

        AuditLog::catat(
            aksi: 'klaim_setujui_bendahara',
            keterangan: "Pengajuan santunan #{$klaim->id} ({$klaim->anggota->nama}) diverifikasi Bendahara sebesar ".WaPesan::rupiah($nominal),
            dataLama: ['status' => 'diajukan'],
            dataBaru: ['status' => 'approved_bendahara', 'nominal_bendahara' => $nominal, 'catatan_bendahara' => $catatan],
            userId: $aktorId,
        );

        WaService::keAnggota(
            $klaim->anggota,
            'klaim_disetujui_bendahara',
            WaPesan::susun($klaim->anggota->nama, $klaim->anggota->no_karyawan,
                'Dengan hormat, pengajuan santunan dana sosial Bapak/Ibu telah *Diverifikasi Bendahara* sebesar '.WaPesan::rupiah($nominal)
                .' dan sedang menunggu keputusan final Ketua Koperasi.'
                .' Perkembangan selanjutnya akan kami sampaikan melalui WhatsApp ini.')
        );
    }

    public function rejectBendahara(KlaimDanaSosial $klaim, string $catatan, ?int $aktorId = null): void
    {
        if ($klaim->status !== 'diajukan') {
            throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat ditolak Bendahara.');
        }

        $klaim->update(['status' => 'ditolak', 'catatan_bendahara' => $catatan]);

        AuditLog::catat(
            aksi: 'klaim_tolak_bendahara',
            keterangan: "Pengajuan santunan dana sosial untuk {$klaim->anggota->nama} ({$klaim->anggota->no_karyawan}) ditolak Bendahara.",
            dataLama: ['status' => 'diajukan'],
            dataBaru: ['status' => 'ditolak', 'catatan_bendahara' => $catatan],
            userId: $aktorId,
        );

        WaService::keAnggota(
            $klaim->anggota,
            'klaim_ditolak',
            WaPesan::susun($klaim->anggota->nama, $klaim->anggota->no_karyawan,
                "Dengan hormat, mohon maaf pengajuan santunan dana sosial Bapak/Ibu belum dapat disetujui oleh Bendahara.\n\nCatatan: {$catatan}\n\n"
                .'Bapak/Ibu dapat mengajukan ulang melalui portal. Apabila memerlukan informasi lebih lanjut, silakan menghubungi pengurus Koperasi.')
        );
    }

    public function approveKetua(KlaimDanaSosial $klaim, float $nominal, string $catatan, ?int $aktorId = null): void
    {
        if ($klaim->status !== 'approved_bendahara') {
            throw new RuntimeException('Hanya pengajuan berstatus Diverifikasi Bendahara yang dapat disetujui Ketua.');
        }

        DB::transaction(function () use ($klaim, $nominal, $catatan, $aktorId) {
            $terkunci = KlaimDanaSosial::lockForUpdate()->findOrFail($klaim->id);

            if ($terkunci->status !== 'approved_bendahara') {
                throw new RuntimeException('Pengajuan sudah diproses sebelumnya.');
            }

            $pengeluaran = $this->pengeluaran->catat(
                'dana_sosial',
                $nominal,
                "Santunan dana sosial ({$this->labelJenis($terkunci->jenis)}) - {$terkunci->anggota->nama}",
                now()->format('Y-m-d'),
                $aktorId ?? $terkunci->anggota->user_id ?? 1,
            );

            $terkunci->update([
                'status' => 'disetujui',
                'nominal_final' => $nominal,
                'catatan_ketua' => $catatan,
                'pengeluaran_id' => $pengeluaran->id,
            ]);
        });

        $klaim->refresh();

        AuditLog::catat(
            aksi: 'klaim_disetujui',
            keterangan: "Santunan dana sosial {$klaim->anggota->nama} disetujui Ketua sebesar ".WaPesan::rupiah($nominal).' (usulan Bendahara: '.WaPesan::rupiah($klaim->nominal_bendahara).')',
            dataLama: ['status' => 'approved_bendahara'],
            dataBaru: ['status' => 'disetujui', 'nominal_final' => $nominal, 'catatan_ketua' => $catatan],
            userId: $aktorId,
        );

        WaService::keAnggota(
            $klaim->anggota,
            'klaim_disetujui',
            WaPesan::susun($klaim->anggota->nama, $klaim->anggota->no_karyawan,
                'Dengan hormat, pengajuan santunan dana sosial Bapak/Ibu telah *DISETUJUI* oleh Ketua Koperasi sebesar '.WaPesan::rupiah($nominal).".\n\n"
                .'Dana santunan akan disalurkan sesuai ketentuan yang berlaku. Terima kasih atas kepercayaan Bapak/Ibu.')
        );
    }

    public function rejectKetua(KlaimDanaSosial $klaim, string $catatan, ?int $aktorId = null): void
    {
        if ($klaim->status !== 'approved_bendahara') {
            throw new RuntimeException('Hanya pengajuan berstatus Diverifikasi Bendahara yang dapat ditolak Ketua.');
        }

        $klaim->update(['status' => 'ditolak', 'catatan_ketua' => $catatan]);

        AuditLog::catat(
            aksi: 'klaim_ditolak',
            keterangan: "Pengajuan santunan dana sosial untuk {$klaim->anggota->nama} ({$klaim->anggota->no_karyawan}) ditolak Ketua.",
            dataLama: ['status' => 'approved_bendahara'],
            dataBaru: ['status' => 'ditolak', 'catatan_ketua' => $catatan],
            userId: $aktorId,
        );

        WaService::keAnggota(
            $klaim->anggota,
            'klaim_ditolak',
            WaPesan::susun($klaim->anggota->nama, $klaim->anggota->no_karyawan,
                "Dengan hormat, mohon maaf pengajuan santunan dana sosial Bapak/Ibu belum dapat disetujui oleh Ketua Koperasi.\n\nCatatan: {$catatan}\n\n"
                .'Apabila memerlukan informasi lebih lanjut, silakan menghubungi pengurus Koperasi.')
        );
    }

    private function validasiJenis(array $data): void
    {
        if ($data['jenis'] === 'sakit') {
            if (! in_array($data['sub_tipe'] ?? null, KlaimDanaSosial::SUB_TIPE_SAKIT, true)) {
                throw new RuntimeException('Jenis perawatan wajib dipilih (Rawat Jalan / Rawat Inap).');
            }

            $minimal = ($data['sub_tipe'] ?? null) === 'rajal' ? 3 : 1;

            if ((int) ($data['lama_hari'] ?? 0) < $minimal) {
                throw new RuntimeException("Lama perawatan minimal {$minimal} hari untuk jenis ini.");
            }
        }

        if ($data['jenis'] === 'duka' && ! in_array($data['hubungan'] ?? null, KlaimDanaSosial::HUBUNGAN_DUKA, true)) {
            throw new RuntimeException('Hubungan keluarga wajib dipilih.');
        }
    }

    public function labelJenis(string $jenis): string
    {
        return match ($jenis) {
            'sakit' => 'Santunan Sakit',
            'lahiran_khitan' => 'Santunan Kelahiran / Khitan',
            'duka' => 'Santunan Duka',
            'bahagia_menikah' => 'Santunan Pernikahan',
            default => $jenis,
        };
    }

    public function hapusFoto(KlaimDanaSosial $klaim): void
    {
        if ($klaim->foto_path) {
            Storage::disk('public')->delete($klaim->foto_path);
        }
    }
}

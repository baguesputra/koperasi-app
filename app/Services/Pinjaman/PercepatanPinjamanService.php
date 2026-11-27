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

    private function tolakBilaSelfApproval(PengajuanPercepatan $pengajuan, ?int $aktorId = null): void
    {
        $aktorId ??= auth()->id();
        $pengajuan->loadMissing('pinjaman.anggota');
        if ($aktorId !== null && $pengajuan->pinjaman?->anggota?->user_id !== null && (int) $pengajuan->pinjaman->anggota->user_id === (int) $aktorId) {
            throw new RuntimeException('Anda tidak dapat memproses pengajuan perubahan tenor milik sendiri.');
        }
    }

    public function ajukan(Pinjaman $pinjaman, string $tipe, ?int $tenorBaru, string $keterangan): PengajuanPercepatan
    {
        $pinjaman->loadMissing('anggota.user');
        $adaPinjamanBerjalan = $pinjaman->anggota->pinjaman()
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->exists();

        if ($adaPinjamanBerjalan) {
            throw new RuntimeException('Pengajuan pinjaman sedang diproses.');
        }

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

        $mandiriBendahara = $pinjaman->anggota?->user?->hasRole('bendahara')
            && ! $pinjaman->anggota->user->hasRole('ketua_koperasi');
        $statusAwal = $mandiriBendahara ? 'approved_bendahara' : 'diajukan';

        $pengajuan = PengajuanPercepatan::create([
            'pinjaman_id' => $pinjaman->id,
            'tipe' => $tipe,
            'tenor_lama' => $pinjaman->tenor_bulan,
            'tenor_baru' => in_array($tipe, ['percepat', 'perpanjang']) ? $tenorBaru : null,
            'keterangan' => $keterangan,
            'status' => $statusAwal,
            'catatan_bendahara' => $mandiriBendahara ? 'Diajukan mandiri oleh Bendahara' : null,
            'tanggal_pengajuan' => now(),
        ]);

        AuditLog::catat(
            aksi: 'percepatan_diajukan',
            keterangan: 'Pengajuan perubahan tenor '.self::labelTipe($tipe)." untuk pinjaman #{$pinjaman->id} ({$pinjaman->anggota->nama}) diajukan. Nominal pinjaman: ".WaPesan::rupiah($pinjaman->nominal)
                .($mandiriBendahara ? ' (pengajuan mandiri Bendahara: langsung ke Ketua).' : ''),
            dataLama: null,
            dataBaru: [
                'pengajuan_id' => $pengajuan->id,
                'pinjaman_id' => $pinjaman->id,
                'tipe' => $tipe,
                'tenor_lama' => $pinjaman->tenor_bulan,
                'tenor_baru' => $tenorBaru,
                'keterangan' => $keterangan,
                'status' => $statusAwal,
            ]
        );

        $labelTipe = self::labelTipe($tipe);

        WaService::keAnggota(
            $pinjaman->anggota,
            'perubahan_tenor_diajukan',
            WaPesan::susun($pinjaman->anggota->nama, $pinjaman->anggota->no_karyawan,
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
                ."- Pemohon: {$pinjaman->anggota->nama} (No. Karyawan: {$pinjaman->anggota->no_karyawan})\n"
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
        $attrs = $pinjaman->getAttributes();
        if (array_key_exists('angsuran_belum_bayar_sum_nominal_pokok', $attrs)) {
            $sisaPokok = (float) ($attrs['angsuran_belum_bayar_sum_nominal_pokok'] ?? 0);
        } elseif ($pinjaman->relationLoaded('angsuranBelumBayar')) {
            $sisaPokok = (float) $pinjaman->getRelation('angsuranBelumBayar')->sum('nominal_pokok');
        } elseif ($pinjaman->relationLoaded('angsuran')) {
            $sisaPokok = (float) $pinjaman->getRelation('angsuran')->where('status', 'belum_bayar')->sum('nominal_pokok');
        } else {
            $sisaPokok = (float) $pinjaman->angsuranBelumBayar()->sum('nominal_pokok');
        }
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

    public function approveBendahara(PengajuanPercepatan $p, string $catatan, ?int $aktorId = null, ?string $bulanBerlaku = null): void
    {
        $this->tolakBilaSelfApproval($p, $aktorId);

        if ($p->status !== 'diajukan') {
            throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat diverifikasi Bendahara.');
        }

        $p->loadMissing('pinjaman.anggota.user');
        $mandiriKetua = $p->pinjaman?->anggota?->user?->hasRole('ketua_koperasi') ?? false;

        if ($mandiriKetua) {
            $bulanFinal = $bulanBerlaku ?? ($p->tipe === 'lunas_total' ? 'bulan_ini' : 'bulan_depan');
            $this->terapkanJadwalFinal(
                $p,
                'Disetujui tanpa self-approval Ketua (diverifikasi Bendahara)',
                $bulanFinal,
                $catatan
            );

            return;
        }

        $statusLama = $p->status;
        $p->update(['status' => 'approved_bendahara', 'catatan_bendahara' => $catatan]);

        AuditLog::catat(
            aksi: 'percepatan_setujui_bendahara',
            keterangan: "Pengajuan percepatan #{$p->id} (pinjaman #{$p->pinjaman->id}) disetujui oleh Bendahara. Jenis: ".self::labelTipe($p->tipe),
            dataLama: ['status' => $statusLama],
            dataBaru: ['status' => 'approved_bendahara', 'catatan_bendahara' => $catatan]
        );
    }

    public function rejectBendahara(PengajuanPercepatan $p, string $catatan, ?int $aktorId = null): void
    {
        $this->tolakBilaSelfApproval($p, $aktorId);

        if ($p->status !== 'diajukan') {
            throw new RuntimeException('Hanya pengajuan berstatus Diajukan yang dapat ditolak Bendahara.');
        }

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

    public function rejectKetua(PengajuanPercepatan $p, string $catatan, ?int $aktorId = null): void
    {
        $this->tolakBilaSelfApproval($p, $aktorId);

        if ($p->status !== 'approved_bendahara') {
            throw new RuntimeException('Hanya pengajuan berstatus Disetujui Bendahara yang dapat ditolak Ketua.');
        }

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

    public function approveKetua(PengajuanPercepatan $pengajuan, string $catatan, string $bulanBerlaku, ?int $aktorId = null): void
    {
        $this->tolakBilaSelfApproval($pengajuan, $aktorId);

        if ($pengajuan->status !== 'approved_bendahara') {
            throw new RuntimeException('Hanya pengajuan berstatus Disetujui Bendahara yang dapat disetujui Ketua.');
        }

        $this->terapkanJadwalFinal($pengajuan, $catatan, $bulanBerlaku);
    }

    private function terapkanJadwalFinal(
        PengajuanPercepatan $pengajuan,
        string $catatanKetua,
        string $bulanBerlaku,
        ?string $catatanBendaharaOverride = null,
    ): void {
        $statusLama = $pengajuan->status;
        DB::transaction(function () use ($pengajuan, $catatanKetua, $bulanBerlaku, $catatanBendaharaOverride) {
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

            $payload = [
                'status' => 'aktif',
                'catatan_ketua' => $catatanKetua,
                'bulan_berlaku' => $bulanBerlaku,
                'sisa_pokok_saat_approval' => $sisaPokok,
            ];
            if ($catatanBendaharaOverride !== null) {
                $payload['catatan_bendahara'] = $catatanBendaharaOverride;
            }
            $pengajuan->update($payload);

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
            keterangan: "Pengajuan percepatan #{$pengajuan->id} (pinjaman #{$pengajuan->pinjaman->id}) disetujui. Jenis: ".self::labelTipe($pengajuan->tipe).", berlaku: {$bulanBerlaku}",
            dataLama: ['status' => $statusLama],
            dataBaru: [
                'status' => 'aktif',
                'catatan_ketua' => $catatanKetua,
                'bulan_berlaku' => $bulanBerlaku,
                'sisa_pokok_saat_approval' => $pengajuan->fresh()->sisa_pokok_saat_approval,
                'nominal_final' => $pengajuan->fresh()->nominal_final,
            ]
        );

        $labelBulan = $bulanBerlaku === 'bulan_ini' ? 'bulan ini ('.now()->translatedFormat('F Y').')' : 'bulan depan ('.now()->addMonthNoOverflow()->translatedFormat('F Y').')';
        $isi = 'Selamat! Pengajuan '.self::labelTipe($pengajuan->tipe)." Anda untuk pinjaman #{$pengajuan->pinjaman->id} ("
            .WaPesan::rupiah($pengajuan->pinjaman->nominal).") telah *DISETUJUI* dan berlaku mulai {$labelBulan}."
            .($catatanKetua ? "\n\nCatatan: {$catatanKetua}" : '')
            ."\n\nJadwal angsuran terbaru dapat dilihat pada sistem koperasi. Terima kasih.";

        WaService::keAnggota(
            $pengajuan->pinjaman->anggota,
            'perubahan_tenor_disetujui_ketua',
            WaPesan::susun($pengajuan->pinjaman->anggota->nama, $pengajuan->pinjaman->anggota->no_karyawan, $isi)
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

        return WaPesan::susun($p->pinjaman->anggota->nama, $p->pinjaman->anggota->no_karyawan, $isi);
    }
}

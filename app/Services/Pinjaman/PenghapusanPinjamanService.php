<?php

namespace App\Services\Pinjaman;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\AuditLog;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\Pinjaman;
use App\Models\User;
use App\Services\Dokumen\PenomoranDokumenService;
use App\Services\Wa\WaPesan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenghapusanPinjamanService
{
    /**
     * Hitung rincian pokok & bunga terbayar serta sisa pokok & bunga belum terbayar
     * dari jadwal aktif pinjaman (selaras dengan perhitungan Piutang Neraca).
     */
    public function hitungRincianPinjaman(Pinjaman $pinjaman): array
    {
        $jadwal = $pinjaman->jadwalAktif();
        $lunas = $jadwal->where('status', 'lunas');
        $belum = $jadwal->where('status', 'belum_bayar')->sortBy('cicilan_ke')->values();

        $nominalPinjaman = round((float) $pinjaman->nominal, 2);
        $totalPokokTerbayar = round((float) $lunas->sum('nominal_pokok'), 2);
        $totalBungaTerbayar = round((float) $lunas->sum('nominal_bunga'), 2);

        $sisaCicilan = $belum->count();
        $sisaPokok = round(max(0.0, $nominalPinjaman - $totalPokokTerbayar), 2);
        $sisaBunga = round((float) $belum->sum('nominal_bunga'), 2);
        $sisaTotalTagihan = round($sisaPokok + $sisaBunga, 2);

        $hariIni = now()->startOfDay();
        $terlambat = $belum->filter(fn ($a) => $a->tanggal_jatuh_tempo && $a->tanggal_jatuh_tempo->lt($hariIni));
        $jatuhTempoTertua = $belum->first()?->tanggal_jatuh_tempo;

        return [
            'nominal_pinjaman' => $nominalPinjaman,
            'total_pokok_terbayar' => $totalPokokTerbayar,
            'total_bunga_terbayar' => $totalBungaTerbayar,
            'sisa_cicilan' => $sisaCicilan,
            'sisa_pokok' => $sisaPokok,
            'sisa_bunga' => $sisaBunga,
            'sisa_total_tagihan' => $sisaTotalTagihan,
            'jumlah_terlambat' => $terlambat->count(),
            'jatuh_tempo_tertua' => $jatuhTempoTertua?->format('d M Y'),
            'jatuh_tempo_tertua_iso' => $jatuhTempoTertua?->format('Y-m-d'),
            'cicilan_awal_belum_bayar' => $belum->first()?->cicilan_ke,
        ];
    }

    /**
     * Daftar pinjaman aktif yang memiliki cicilan belum terbayar dan belum memiliki
     * pengajuan penghapusan aktif (diajukan / dalam proses approval).
     */
    public function daftarPinjamanMenunggak(): Collection
    {
        $pinjamanBerpengajuan = PengajuanPenghapusanPinjaman::whereNotIn('status', ['ditolak'])
            ->pluck('pinjaman_id')
            ->all();

        return Pinjaman::with([
            'anggota',
            'angsuran' => fn ($q) => $q->where('status', '!=', 'digantikan')->orderBy('cicilan_ke'),
            'pengajuanPercepatan' => fn ($q) => $q->where('status', 'aktif')->latest()->with('angsuranBaru'),
        ])
            ->where('status', 'aktif')
            ->when(! empty($pinjamanBerpengajuan), fn ($q) => $q->whereNotIn('id', $pinjamanBerpengajuan))
            ->orderBy('tanggal_pencairan')
            ->get()
            ->map(function (Pinjaman $p) {
                $rincian = $this->hitungRincianPinjaman($p);
                if ($rincian['sisa_cicilan'] <= 0 || $rincian['sisa_pokok'] <= 0) {
                    return null;
                }

                return [
                    'pinjaman_id' => $p->id,
                    'nomor_dokumen' => $p->nomor_dokumen ?? ('#'.$p->id),
                    'tanggal_cair' => $p->tanggal_pencairan?->format('d M Y') ?? $p->tanggal_pengajuan?->format('d M Y') ?? '-',
                    'tenor_bulan' => $p->tenor_bulan,
                    'anggota' => [
                        'id' => $p->anggota?->id,
                        'nama' => $p->anggota?->nama ?? '-',
                        'no_karyawan' => $p->anggota?->no_karyawan ?? '-',
                        'no_anggota' => $p->anggota?->no_anggota ?? '-',
                        'cabang' => $p->anggota?->cabang ?? '-',
                        'foto_url' => $p->anggota?->foto_url,
                        'status' => $p->anggota?->status ?? '-',
                    ],
                    ...$rincian,
                ];
            })
            ->filter()
            ->sortByDesc('jumlah_terlambat')
            ->values();
    }

    /**
     * Cek apakah user yang login berhak melakukan approval/reject pada tahap saat ini.
     */
    public function bisaDiprosesOleh(PengajuanPenghapusanPinjaman $pengajuan, User $user): bool
    {
        if (in_array($pengajuan->status, ['disetujui', 'ditolak'], true)) {
            return false;
        }

        if ($pengajuan->anggota?->user_id && $pengajuan->anggota->user_id === $user->id) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($pengajuan->pengaju_user_id === $user->id) {
            return false;
        }

        return match ($pengajuan->status) {
            'diajukan' => $user->can('penghapusan-pinjaman.approve-bendahara'),
            'approved_bendahara' => $user->can('penghapusan-pinjaman.approve-ketua'),
            'approved_ketua' => $user->can('penghapusan-pinjaman.approve-pengawas') && $user->levelPengawas() === 3,
            'approved_pengawas_3' => $user->can('penghapusan-pinjaman.approve-pengawas') && $user->levelPengawas() === 2,
            'approved_pengawas_2' => $user->can('penghapusan-pinjaman.approve-pengawas') && $user->levelPengawas() === 1,
            default => false,
        };
    }

    /**
     * Hitung jumlah pengajuan penghapusan pinjaman yang sedang menunggu giliran user ini.
     */
    public function hitungAntreanUntukUser(User $user): int
    {
        if ($user->hasRole('super_admin')) {
            return PengajuanPenghapusanPinjaman::whereIn('status', [
                'diajukan',
                'approved_bendahara',
                'approved_ketua',
                'approved_pengawas_3',
                'approved_pengawas_2',
            ])->count();
        }

        $statuses = [];

        if ($user->can('penghapusan-pinjaman.approve-bendahara')) {
            $statuses[] = 'diajukan';
        }
        if ($user->can('penghapusan-pinjaman.approve-ketua')) {
            $statuses[] = 'approved_bendahara';
        }
        if ($user->can('penghapusan-pinjaman.approve-pengawas')) {
            $level = $user->levelPengawas();
            if ($level === 3) {
                $statuses[] = 'approved_ketua';
            } elseif ($level === 2) {
                $statuses[] = 'approved_pengawas_3';
            } elseif ($level === 1) {
                $statuses[] = 'approved_pengawas_2';
            }
        }

        if (empty($statuses)) {
            return 0;
        }

        return PengajuanPenghapusanPinjaman::whereIn('status', $statuses)
            ->where(fn ($q) => $q->whereNull('pengaju_user_id')->orWhere('pengaju_user_id', '!=', $user->id))
            ->count();
    }

    /**
     * Pengurus (Sekretaris atau Bendahara) mengajukan penghapusan pinjaman menunggak.
     */
    public function ajukan(Pinjaman $pinjaman, string $alasan, User $aktor): PengajuanPenghapusanPinjaman
    {
        $alasan = trim($alasan);

        return DB::transaction(function () use ($pinjaman, $alasan, $aktor) {
            $pinjamanLocked = Pinjaman::with('anggota')->lockForUpdate()->findOrFail($pinjaman->id);

            if ($pinjamanLocked->status !== 'aktif') {
                throw new RuntimeException('Hanya pinjaman berstatus Aktif yang dapat diajukan penghapusan.');
            }

            if ($pinjamanLocked->anggota?->user_id && $pinjamanLocked->anggota->user_id === $aktor->id) {
                throw new RuntimeException('Anda tidak dapat mengajukan penghapusan untuk pinjaman Anda sendiri.');
            }

            $sudahAda = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjamanLocked->id)
                ->whereNotIn('status', ['ditolak'])
                ->lockForUpdate()
                ->exists();

            if ($sudahAda) {
                throw new RuntimeException('Pinjaman ini sudah memiliki pengajuan penghapusan yang sedang diproses atau telah disetujui.');
            }

            $pinjamanLocked->refresh();
            $rincian = $this->hitungRincianPinjaman($pinjamanLocked);

            if ($rincian['sisa_cicilan'] <= 0 || $rincian['sisa_pokok'] <= 0) {
                throw new RuntimeException('Pinjaman ini tidak memiliki sisa pokok angsuran yang belum dibayar.');
            }

            // Jika diajukan langsung oleh Bendahara, tahap verifikasi Bendahara otomatis terpenuhi
            // agar langsung masuk ke antrean Ketua Koperasi (hindari self-approval).
            $diajukanBendahara = $aktor->hasRole('bendahara') && ! $aktor->hasRole('sekretaris');
            $statusAwal = $diajukanBendahara ? 'approved_bendahara' : 'diajukan';

            $pengajuan = PengajuanPenghapusanPinjaman::create([
                'pinjaman_id' => $pinjamanLocked->id,
                'anggota_id' => $pinjamanLocked->anggota_id,
                'pengaju_user_id' => $aktor->id,
                'tanggal_pengajuan' => now()->toDateString(),
                'alasan' => $alasan,
                'nominal_pinjaman' => $rincian['nominal_pinjaman'],
                'total_pokok_terbayar' => $rincian['total_pokok_terbayar'],
                'total_bunga_terbayar' => $rincian['total_bunga_terbayar'],
                'sisa_cicilan' => $rincian['sisa_cicilan'],
                'sisa_pokok' => $rincian['sisa_pokok'],
                'sisa_bunga' => $rincian['sisa_bunga'],
                'sisa_total_tagihan' => $rincian['sisa_total_tagihan'],
                'status' => $statusAwal,
                'approved_bendahara_by' => $diajukanBendahara ? $aktor->id : null,
                'approved_bendahara_at' => $diajukanBendahara ? now() : null,
                'catatan_bendahara' => $diajukanBendahara ? "Diajukan langsung oleh Bendahara: {$alasan}" : null,
            ]);

            AuditLog::catat(
                aksi: 'penghapusan_pinjaman_diajukan',
                keterangan: "Pengajuan penghapusan pinjaman #{$pinjamanLocked->id} ({$pinjamanLocked->anggota->nama}) diajukan oleh {$aktor->name}. "
                    .'Sisa pokok dihapusbukukan: '.WaPesan::rupiah($rincian['sisa_pokok'])
                    ." ({$rincian['sisa_cicilan']}x sisa cicilan). Alasan: {$alasan}",
                dataLama: ['pinjaman_status' => $pinjamanLocked->status],
                dataBaru: [
                    'pengajuan_id' => $pengajuan->id,
                    'pinjaman_id' => $pinjamanLocked->id,
                    'anggota_id' => $pinjamanLocked->anggota_id,
                    'sisa_pokok' => $rincian['sisa_pokok'],
                    'sisa_bunga' => $rincian['sisa_bunga'],
                    'status' => $statusAwal,
                ],
                userId: $aktor->id,
            );

            return $pengajuan;
        });
    }

    /**
     * Proses persetujuan berjenjang sesuai status pengajuan saat ini:
     * diajukan -> approved_bendahara -> approved_ketua -> approved_pengawas_3 -> approved_pengawas_2 -> disetujui (Pengawas 1 / Ketua Pengawas).
     */
    public function approve(PengajuanPenghapusanPinjaman $pengajuan, string $catatan, User $aktor): PengajuanPenghapusanPinjaman
    {
        $catatan = trim($catatan);

        return DB::transaction(function () use ($pengajuan, $catatan, $aktor) {
            $locked = PengajuanPenghapusanPinjaman::with(['pinjaman.anggota', 'anggota'])
                ->lockForUpdate()
                ->findOrFail($pengajuan->id);

            $this->validasiHakProses($locked, $aktor);

            return match ($locked->status) {
                'diajukan' => $this->eksekusiApproveBendahara($locked, $catatan, $aktor),
                'approved_bendahara' => $this->eksekusiApproveKetua($locked, $catatan, $aktor),
                'approved_ketua' => $this->eksekusiApprovePengawas3($locked, $catatan, $aktor),
                'approved_pengawas_3' => $this->eksekusiApprovePengawas2($locked, $catatan, $aktor),
                'approved_pengawas_2' => $this->eksekusiApprovePengawas1Final($locked, $catatan, $aktor),
                default => throw new RuntimeException('Pengajuan penghapusan pinjaman ini tidak dalam status menunggu persetujuan.'),
            };
        });
    }

    /**
     * Tolak pengajuan penghapusan pinjaman pada tahap aktif saat ini.
     */
    public function reject(PengajuanPenghapusanPinjaman $pengajuan, string $catatan, User $aktor): PengajuanPenghapusanPinjaman
    {
        $catatan = trim($catatan);

        return DB::transaction(function () use ($pengajuan, $catatan, $aktor) {
            $locked = PengajuanPenghapusanPinjaman::with(['pinjaman.anggota', 'anggota'])
                ->lockForUpdate()
                ->findOrFail($pengajuan->id);

            $this->validasiHakProses($locked, $aktor);

            $statusLama = $locked->status;
            $tahap = match ($statusLama) {
                'diajukan' => 'bendahara',
                'approved_bendahara' => 'ketua',
                'approved_ketua' => 'pengawas_3',
                'approved_pengawas_3' => 'pengawas_2',
                'approved_pengawas_2' => 'pengawas_1',
                default => 'pengurus',
            };

            $locked->update([
                'status' => 'ditolak',
                'ditolak_oleh_user_id' => $aktor->id,
                'ditolak_pada' => now(),
                'tahap_ditolak' => $tahap,
                'catatan_tolak' => $catatan,
            ]);

            AuditLog::catat(
                aksi: 'penghapusan_pinjaman_ditolak',
                keterangan: "Pengajuan penghapusan pinjaman #{$locked->pinjaman_id} ({$locked->anggota->nama}) ditolak pada tahap {$tahap} oleh {$aktor->name}. Catatan: {$catatan}",
                dataLama: ['status' => $statusLama],
                dataBaru: ['status' => 'ditolak', 'tahap_ditolak' => $tahap, 'catatan_tolak' => $catatan],
                userId: $aktor->id,
            );

            return $locked->refresh();
        });
    }

    private function validasiHakProses(PengajuanPenghapusanPinjaman $pengajuan, User $aktor): void
    {
        if (in_array($pengajuan->status, ['disetujui', 'ditolak'], true)) {
            throw new RuntimeException('Pengajuan penghapusan ini sudah selesai diproses.');
        }

        if ($pengajuan->anggota?->user_id && $pengajuan->anggota->user_id === $aktor->id) {
            throw new RuntimeException('Anda tidak dapat memproses pengajuan penghapusan milik Anda sendiri.');
        }

        if (! $aktor->hasRole('super_admin') && $pengajuan->pengaju_user_id === $aktor->id) {
            throw new RuntimeException('Anda tidak dapat menyetujui atau menolak pengajuan yang Anda ajukan sendiri.');
        }

        if ($aktor->hasRole('super_admin')) {
            return;
        }

        match ($pengajuan->status) {
            'diajukan' => $aktor->can('penghapusan-pinjaman.approve-bendahara')
                ?: throw new RuntimeException('Tahap ini hanya dapat diproses oleh Bendahara.'),
            'approved_bendahara' => $aktor->can('penghapusan-pinjaman.approve-ketua')
                ?: throw new RuntimeException('Tahap ini hanya dapat diproses oleh Ketua Koperasi.'),
            'approved_ketua' => ($aktor->can('penghapusan-pinjaman.approve-pengawas') && $aktor->levelPengawas() === 3)
                ?: throw new RuntimeException('Tahap ini menunggu persetujuan Pengawas 3.'),
            'approved_pengawas_3' => ($aktor->can('penghapusan-pinjaman.approve-pengawas') && $aktor->levelPengawas() === 2)
                ?: throw new RuntimeException('Tahap ini menunggu persetujuan Pengawas 2.'),
            'approved_pengawas_2' => ($aktor->can('penghapusan-pinjaman.approve-pengawas') && $aktor->levelPengawas() === 1)
                ?: throw new RuntimeException('Tahap final ini menunggu persetujuan Pengawas 1 (Ketua Pengawas).'),
            default => throw new RuntimeException('Status pengajuan tidak valid.'),
        };
    }

    private function eksekusiApproveBendahara(PengajuanPenghapusanPinjaman $locked, string $catatan, User $aktor): PengajuanPenghapusanPinjaman
    {
        $locked->update([
            'status' => 'approved_bendahara',
            'approved_bendahara_by' => $aktor->id,
            'approved_bendahara_at' => now(),
            'catatan_bendahara' => $catatan,
        ]);

        AuditLog::catat(
            aksi: 'penghapusan_pinjaman_approve_bendahara',
            keterangan: "Penghapusan pinjaman #{$locked->pinjaman_id} ({$locked->anggota->nama}) diverifikasi oleh Bendahara ({$aktor->name}). Sisa pokok: ".WaPesan::rupiah($locked->sisa_pokok),
            dataLama: ['status' => 'diajukan'],
            dataBaru: ['status' => 'approved_bendahara', 'catatan_bendahara' => $catatan],
            userId: $aktor->id,
        );

        return $locked->refresh();
    }

    private function eksekusiApproveKetua(PengajuanPenghapusanPinjaman $locked, string $catatan, User $aktor): PengajuanPenghapusanPinjaman
    {
        $locked->update([
            'status' => 'approved_ketua',
            'approved_ketua_by' => $aktor->id,
            'approved_ketua_at' => now(),
            'catatan_ketua' => $catatan,
        ]);

        AuditLog::catat(
            aksi: 'penghapusan_pinjaman_approve_ketua',
            keterangan: "Penghapusan pinjaman #{$locked->pinjaman_id} ({$locked->anggota->nama}) disetujui oleh Ketua Koperasi ({$aktor->name}), diteruskan ke Pengawas 3. Sisa pokok: ".WaPesan::rupiah($locked->sisa_pokok),
            dataLama: ['status' => 'approved_bendahara'],
            dataBaru: ['status' => 'approved_ketua', 'catatan_ketua' => $catatan],
            userId: $aktor->id,
        );

        return $locked->refresh();
    }

    private function eksekusiApprovePengawas3(PengajuanPenghapusanPinjaman $locked, string $catatan, User $aktor): PengajuanPenghapusanPinjaman
    {
        $locked->update([
            'status' => 'approved_pengawas_3',
            'approved_pengawas_3_by' => $aktor->id,
            'approved_pengawas_3_at' => now(),
            'catatan_pengawas_3' => $catatan,
        ]);

        AuditLog::catat(
            aksi: 'penghapusan_pinjaman_approve_pengawas_3',
            keterangan: "Penghapusan pinjaman #{$locked->pinjaman_id} ({$locked->anggota->nama}) disetujui oleh Pengawas 3 ({$aktor->name}), diteruskan ke Pengawas 2.",
            dataLama: ['status' => 'approved_ketua'],
            dataBaru: ['status' => 'approved_pengawas_3', 'catatan_pengawas_3' => $catatan],
            userId: $aktor->id,
        );

        return $locked->refresh();
    }

    private function eksekusiApprovePengawas2(PengajuanPenghapusanPinjaman $locked, string $catatan, User $aktor): PengajuanPenghapusanPinjaman
    {
        if (! $aktor->hasRole('super_admin') && $locked->approved_pengawas_3_by === $aktor->id) {
            throw new RuntimeException('Akun Pengawas yang sama tidak dapat menyetujui lebih dari satu jenjang.');
        }

        $locked->update([
            'status' => 'approved_pengawas_2',
            'approved_pengawas_2_by' => $aktor->id,
            'approved_pengawas_2_at' => now(),
            'catatan_pengawas_2' => $catatan,
        ]);

        AuditLog::catat(
            aksi: 'penghapusan_pinjaman_approve_pengawas_2',
            keterangan: "Penghapusan pinjaman #{$locked->pinjaman_id} ({$locked->anggota->nama}) disetujui oleh Pengawas 2 ({$aktor->name}), diteruskan ke Pengawas 1 (Ketua Pengawas).",
            dataLama: ['status' => 'approved_pengawas_3'],
            dataBaru: ['status' => 'approved_pengawas_2', 'catatan_pengawas_2' => $catatan],
            userId: $aktor->id,
        );

        return $locked->refresh();
    }

    /**
     * Persetujuan final oleh Pengawas 1 (Ketua Pengawas):
     * 1. Hitung ulang snapshot sisa pokok terkini (jaga bila ada angsuran masuk sebelum finalisasi).
     * 2. Tandai seluruh sisa angsuran & angsuran_percepatan belum_bayar -> dihapusbukukan.
     * 3. Tandai pinjaman -> dihapusbukukan.
     * 4. Opsi 4A: Nonaktifkan anggota & user agar tidak ditagih iuran wajib bulanan baru,
     *    namun simpanan lama tetap utuh mengendap sebagai kewajiban simpanan di Neraca.
     */
    private function eksekusiApprovePengawas1Final(PengajuanPenghapusanPinjaman $locked, string $catatan, User $aktor): PengajuanPenghapusanPinjaman
    {
        if (
            ! $aktor->hasRole('super_admin')
            && in_array($aktor->id, [$locked->approved_pengawas_3_by, $locked->approved_pengawas_2_by], true)
        ) {
            throw new RuntimeException('Akun Pengawas yang sama tidak dapat menyetujui lebih dari satu jenjang.');
        }

        $pinjaman = Pinjaman::lockForUpdate()->findOrFail($locked->pinjaman_id);
        if ($pinjaman->status !== 'aktif') {
            throw new RuntimeException("Pinjaman #{$pinjaman->id} sudah tidak berstatus Aktif (status saat ini: {$pinjaman->status}).");
        }

        $anggota = Anggota::lockForUpdate()->findOrFail($locked->anggota_id);

        // Hitung ulang sisa pokok aktual saat finalisasi agar selalu akurat 100% terhadap Neraca
        $rincianAktual = $this->hitungRincianPinjaman($pinjaman);
        if ($rincianAktual['sisa_pokok'] <= 0) {
            throw new RuntimeException('Pinjaman ini sudah tidak memiliki sisa pokok yang belum dibayar.');
        }

        $nomorDokumen = $locked->nomor_dokumen
            ?? app(PenomoranDokumenService::class)->berikutnya(
                PenomoranDokumenService::JENIS_PENGHAPUSAN_PINJAMAN,
                now()
            );

        // 1. Ubah sisa angsuran biasa yang belum_bayar -> dihapusbukukan
        Angsuran::where('pinjaman_id', $pinjaman->id)
            ->where('status', 'belum_bayar')
            ->update(['status' => 'dihapusbukukan']);

        // 2. Ubah sisa angsuran_percepatan aktif yang belum_bayar -> dihapusbukukan
        $pengajuanPercepatanAktif = $pinjaman->pengajuanPercepatan()->where('status', 'aktif')->pluck('id');
        if ($pengajuanPercepatanAktif->isNotEmpty()) {
            AngsuranPercepatan::whereIn('pengajuan_percepatan_id', $pengajuanPercepatanAktif)
                ->where('status', 'belum_bayar')
                ->update(['status' => 'dihapusbukukan']);
        }

        // 3. Ubah status pinjaman -> dihapusbukukan
        $pinjaman->update(['status' => 'dihapusbukukan']);

        // 4. Opsi 4A: Nonaktifkan anggota & user login agar tidak muncul tagihan simpanan wajib bulanan baru,
        // sementara saldo simpanan yang sudah masuk tetap mengendap utuh (tidak dihapus/dihanguskan).
        $statusAnggotaLama = $anggota->status;
        if ($anggota->status === 'aktif') {
            $anggota->update(['status' => 'nonaktif']);
        }
        if ($anggota->user_id) {
            User::where('id', $anggota->user_id)->update(['status' => 'nonaktif']);
        }

        $waktuFinal = now();
        $locked->update([
            'nomor_dokumen' => $nomorDokumen,
            'total_pokok_terbayar' => $rincianAktual['total_pokok_terbayar'],
            'total_bunga_terbayar' => $rincianAktual['total_bunga_terbayar'],
            'sisa_cicilan' => $rincianAktual['sisa_cicilan'],
            'sisa_pokok' => $rincianAktual['sisa_pokok'],
            'sisa_bunga' => $rincianAktual['sisa_bunga'],
            'sisa_total_tagihan' => $rincianAktual['sisa_total_tagihan'],
            'status' => 'disetujui',
            'approved_pengawas_1_by' => $aktor->id,
            'approved_pengawas_1_at' => $waktuFinal,
            'catatan_pengawas_1' => $catatan,
            'disetujui_pada' => $waktuFinal,
        ]);

        AuditLog::catat(
            aksi: 'penghapusan_pinjaman_disetujui_final',
            keterangan: "Penghapusan pinjaman #{$pinjaman->id} ({$anggota->nama}) DISETUJUI FINAL oleh Pengawas 1 / Ketua Pengawas ({$aktor->name}) dengan dokumen {$nomorDokumen}. "
                .'Sisa pokok dihapusbukukan: '.WaPesan::rupiah($rincianAktual['sisa_pokok'])
                ." ({$rincianAktual['sisa_cicilan']}x angsuran). Status anggota diubah dari '{$statusAnggotaLama}' menjadi 'nonaktif' (simpanan tetap mengendap).",
            dataLama: [
                'status_pengajuan' => 'approved_pengawas_2',
                'status_pinjaman' => 'aktif',
                'status_anggota' => $statusAnggotaLama,
            ],
            dataBaru: [
                'status_pengajuan' => 'disetujui',
                'nomor_dokumen' => $nomorDokumen,
                'status_pinjaman' => 'dihapusbukukan',
                'status_anggota' => $anggota->status,
                'sisa_pokok_dihapus' => $rincianAktual['sisa_pokok'],
                'sisa_bunga_dibatalkan' => $rincianAktual['sisa_bunga'],
                'catatan_pengawas_1' => $catatan,
            ],
            userId: $aktor->id,
        );

        return $locked->refresh();
    }
}

<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Database\Seeder;

class PinjamanSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPinjamanBaru();
        $this->seedPinjamanAktifSebagianLunas();
        $this->seedPinjamanLunasSemua();
        $this->seedPinjamanSisaDuaAngsuran();
        $this->seedPinjamanTambahan();
        $this->seedTopupKas();
    }

    /**
     * Skenario 1: Anggota baru, pinjaman baru diajukan, belum direview.
     */
    private function seedPinjamanBaru(): void
    {
        $anggota = Anggota::where('no_anggota', 'ANG-2026-0001')->first();

        Pinjaman::firstOrCreate(
            ['anggota_id' => $anggota->id, 'status' => 'diajukan'],
            [
                'nominal' => 1_000_000,
                'tenor_bulan' => 3,
                'keperluan' => 'Kebutuhan harian',
                'snapshot_bank' => 'BCA',
                'snapshot_no_rekening' => '1234001001',
                'snapshot_atas_nama' => 'Budi Santoso',
                'persentase_bunga' => 1.00,
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now()->subDays(2),
            ]
        );
    }

    /**
     * Skenario 2: Anggota sedang, pinjaman aktif, 2 dari 4 angsuran sudah lunas.
     */
    private function seedPinjamanAktifSebagianLunas(): void
    {
        $anggota = Anggota::where('no_anggota', 'ANG-2023-0045')->first();

        $pinjaman = Pinjaman::firstOrCreate(
            ['anggota_id' => $anggota->id, 'status' => 'aktif'],
            [
                'nominal' => 2_000_000,
                'tenor_bulan' => 4,
                'keperluan' => 'Biaya pendidikan anak',
                'snapshot_bank' => 'Mandiri',
                'snapshot_no_rekening' => '8213400220',
                'snapshot_atas_nama' => 'Siti Aminah',
                'persentase_bunga' => 1.00,
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now()->subMonths(3),
                'tanggal_pencairan' => now()->subMonths(3)->addDays(3),
            ]
        );

        $this->generateJadwalAngsuran($pinjaman, lunasSampaiCicilanKe: 2);
        $this->catatMutasiPinjaman($pinjaman);
    }

    /**
     * Skenario 3: Anggota lama, pinjaman lunas semua (histori).
     */
    private function seedPinjamanLunasSemua(): void
    {
        $anggota = Anggota::where('no_anggota', 'ANG-2019-0012')->first();

        $pinjaman = Pinjaman::firstOrCreate(
            ['anggota_id' => $anggota->id, 'status' => 'lunas'],
            [
                'nominal' => 3_000_000,
                'tenor_bulan' => 6,
                'keperluan' => 'Perbaikan rumah',
                'snapshot_bank' => 'BRI',
                'snapshot_no_rekening' => '72810033',
                'snapshot_atas_nama' => 'Ahmad Ridwan',
                'persentase_bunga' => 1.00,
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now()->subMonths(8),
                'tanggal_pencairan' => now()->subMonths(8)->addDays(3),
            ]
        );

        $this->generateJadwalAngsuran($pinjaman, lunasSampaiCicilanKe: 6);
        $this->catatMutasiPinjaman($pinjaman);
    }

    /**
     * Skenario 4: Anggota lama, pinjaman aktif tersisa 2x angsuran
     * (untuk test privilege reloan — anggota ini boleh ajukan pinjaman baru lagi).
     */
    private function seedPinjamanSisaDuaAngsuran(): void
    {
        $anggota = Anggota::where('no_anggota', 'ANG-2018-0003')->first();

        $pinjaman = Pinjaman::firstOrCreate(
            ['anggota_id' => $anggota->id, 'status' => 'aktif'],
            [
                'nominal' => 5_000_000,
                'tenor_bulan' => 12,
                'keperluan' => 'Pembelian kendaraan',
                'snapshot_bank' => 'BNI',
                'snapshot_no_rekening' => '20987654',
                'snapshot_atas_nama' => 'Dewi Lestari',
                'persentase_bunga' => 1.00,
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now()->subMonths(10),
                'tanggal_pencairan' => now()->subMonths(10)->addDays(3),
            ]
        );

        // 10 dari 12 cicilan lunas, sisa 2 -> memenuhi syarat privilege reloan
        $this->generateJadwalAngsuran($pinjaman, lunasSampaiCicilanKe: 10);
        $this->catatMutasiPinjaman($pinjaman);
    }

    /**
     * Tambahan pinjaman tersebar di anggota lain dengan status beragam.
     */
    private function seedPinjamanTambahan(): void
    {
        $data = [
            // Sedang menunggu tinjauan Bendahara
            ['no_karyawan' => 'TOP-100006', 'status' => 'diajukan', 'nominal' => 1_500_000, 'tenor_bulan' => 4, 'keperluan' => 'Kebutuhan hari raya', 'bank' => 'BCA', 'no_rekening' => '1234002002', 'atas_nama' => 'Agus Wijaya', 'tanggal_pengajuan' => now()->subDays(1)],
            ['no_karyawan' => 'TOP-100016', 'status' => 'diajukan', 'nominal' => 2_500_000, 'tenor_bulan' => 6, 'keperluan' => 'Biaya pendidikan anak', 'bank' => 'Mandiri', 'no_rekening' => '8213400221', 'atas_nama' => 'Adi Nugroho', 'tanggal_pengajuan' => now()->subDays(3)],
            ['no_karyawan' => 'TOP-100026', 'status' => 'diajukan', 'nominal' => 5_000_000, 'tenor_bulan' => 12, 'keperluan' => 'Perbaikan rumah', 'bank' => 'BRI', 'no_rekening' => '72810034', 'atas_nama' => 'Deni Setiawan', 'tanggal_pengajuan' => now()->subDays(5)],

            // Sudah disetujui Bendahara, menunggu approval Ketua
            ['no_karyawan' => 'TOP-100008', 'status' => 'approved_bendahara', 'nominal' => 3_500_000, 'tenor_bulan' => 9, 'keperluan' => 'Biaya pengobatan', 'bank' => 'BNI', 'no_rekening' => '20987655', 'atas_nama' => 'Maya Sari', 'tanggal_pengajuan' => now()->subDays(8), 'catatan_bendahara' => 'Verifikasi dokumen lengkap, layak diteruskan ke Ketua.'],
            ['no_karyawan' => 'TOP-100018', 'status' => 'approved_bendahara', 'nominal' => 6_000_000, 'tenor_bulan' => 12, 'keperluan' => 'Pembelian kendaraan', 'bank' => 'Bank Kalsel', 'no_rekening' => '55990011', 'atas_nama' => 'Yudha Pradana', 'tanggal_pengajuan' => now()->subDays(10), 'catatan_bendahara' => 'Riwayat angsuran baik, disetujui.'],
            ['no_karyawan' => 'TOP-100028', 'status' => 'approved_bendahara', 'nominal' => 2_000_000, 'tenor_bulan' => 4, 'keperluan' => 'Modal usaha', 'bank' => 'BCA', 'no_rekening' => '1234003003', 'atas_nama' => 'Galih Prakoso', 'tanggal_pengajuan' => now()->subDays(12), 'catatan_bendahara' => 'Dokumen sesuai ketentuan.'],

            // Pinjaman aktif dengan sebagian angsuran sudah dibayar (lanjut berjalan)
            ['no_karyawan' => 'TOP-100007', 'status' => 'aktif', 'nominal' => 1_000_000, 'tenor_bulan' => 3, 'keperluan' => 'Perlengkapan rumah tangga', 'bank' => 'BCA', 'no_rekening' => '1234004004', 'atas_nama' => 'Hendra Gunawan', 'tanggal_pengajuan' => now()->subMonths(2), 'tanggal_pencairan' => now()->subMonths(2)->addDays(3), 'lunas_sampai' => 1],
            ['no_karyawan' => 'TOP-100017', 'status' => 'aktif', 'nominal' => 2_000_000, 'tenor_bulan' => 4, 'keperluan' => 'Biaya pendidikan anak', 'bank' => 'Mandiri', 'no_rekening' => '8213400222', 'atas_nama' => 'Indah Permata', 'tanggal_pengajuan' => now()->subMonths(3), 'tanggal_pencairan' => now()->subMonths(3)->addDays(3), 'lunas_sampai' => 2],
            ['no_karyawan' => 'TOP-100010', 'status' => 'aktif', 'nominal' => 3_000_000, 'tenor_bulan' => 6, 'keperluan' => 'Perbaikan rumah', 'bank' => 'BRI', 'no_rekening' => '72810035', 'atas_nama' => 'Joko Susanto', 'tanggal_pengajuan' => now()->subMonths(5), 'tanggal_pencairan' => now()->subMonths(5)->addDays(3), 'lunas_sampai' => 3],

            // Pinjaman yang sudah lunas (histori)
            ['no_karyawan' => 'TOP-100030', 'status' => 'lunas', 'nominal' => 4_000_000, 'tenor_bulan' => 9, 'keperluan' => 'Modal usaha', 'bank' => 'BNI', 'no_rekening' => '20987656', 'atas_nama' => 'Ferry Ardiansyah', 'tanggal_pengajuan' => now()->subMonths(10), 'tanggal_pencairan' => now()->subMonths(10)->addDays(3), 'lunas_sampai' => 9],
            ['no_karyawan' => 'TOP-100040', 'status' => 'lunas', 'nominal' => 6_000_000, 'tenor_bulan' => 12, 'keperluan' => 'Pembelian kendaraan', 'bank' => 'Bank Kalsel', 'no_rekening' => '55990012', 'atas_nama' => 'Candra Wijaya', 'tanggal_pengajuan' => now()->subMonths(14), 'tanggal_pencairan' => now()->subMonths(14)->addDays(3), 'lunas_sampai' => 12],
            ['no_karyawan' => 'TOP-100050', 'status' => 'lunas', 'nominal' => 2_500_000, 'tenor_bulan' => 6, 'keperluan' => 'Kebutuhan hari raya', 'bank' => 'BCA', 'no_rekening' => '1234005005', 'atas_nama' => 'Citra Ramadhani', 'tanggal_pengajuan' => now()->subMonths(8), 'tanggal_pencairan' => now()->subMonths(8)->addDays(3), 'lunas_sampai' => 6],
        ];

        foreach ($data as $d) {
            $anggota = Anggota::where('no_karyawan', $d['no_karyawan'])->first();

            if (! $anggota) {
                continue;
            }

            $pinjaman = Pinjaman::firstOrCreate(
                ['anggota_id' => $anggota->id, 'status' => $d['status']],
                $this->atributPinjaman($d, $anggota->id)
            );

            if (in_array($d['status'], ['aktif', 'lunas'])) {
                $this->generateJadwalAngsuran($pinjaman, lunasSampaiCicilanKe: $d['lunas_sampai']);
            }

            $this->catatMutasiPinjaman($pinjaman);
        }
    }

    private function atributPinjaman(array $d, int $anggotaId): array
    {
        return [
            'anggota_id' => $anggotaId,
            'nominal' => $d['nominal'],
            'tenor_bulan' => $d['tenor_bulan'],
            'keperluan' => $d['keperluan'] ?? null,
            'snapshot_bank' => $d['bank'] ?? 'BCA',
            'snapshot_no_rekening' => $d['no_rekening'] ?? null,
            'snapshot_atas_nama' => $d['atas_nama'] ?? null,
            'persentase_bunga' => 1.00,
            'status' => $d['status'],
            'sudah_pakai_privilege_reloan' => false,
            'tanggal_pengajuan' => $d['tanggal_pengajuan'],
            'tanggal_pencairan' => $d['tanggal_pencairan'] ?? null,
            'catatan_bendahara' => $d['catatan_bendahara'] ?? null,
        ];
    }

    /**
     * Catat mutasi kas: pencairan (keluar) & angsuran lunas (masuk) ke jurnal,
     * lalu sesuaikan saldo kas. Idempoten (firstOrCreate by referensi).
     */
    private function catatMutasiPinjaman(Pinjaman $pinjaman): void
    {
        $kas = KasKoperasi::firstOrCreate(['id' => 1], ['saldo_pinjaman' => 0]);
        $benId = $this->benId();
        $ketuaId = $this->ketuaId();

        // Pencairan pinjaman -> kas keluar
        if ($pinjaman->tanggal_pencairan && in_array($pinjaman->status, ['aktif', 'lunas'])) {
            $jurnal = JurnalKas::firstOrCreate(
                ['tipe' => 'keluar', 'kategori' => 'pencairan_pinjaman', 'referensi_id' => $pinjaman->id],
                [
                    'jumlah' => $pinjaman->nominal,
                    'keterangan' => "Pencairan pinjaman - {$pinjaman->anggota->nama}",
                    'tanggal' => $pinjaman->tanggal_pencairan,
                    'created_by' => $ketuaId,
                ]
            );

            if ($jurnal->wasRecentlyCreated) {
                $kas->decrement('saldo_pinjaman', $pinjaman->nominal);
            }
        }

        // Angsuran lunas -> kas masuk
        $angsuranLunas = $pinjaman->angsuran()
            ->where('status', 'lunas')
            ->whereNotNull('tanggal_konfirmasi_bayar')
            ->get();

        foreach ($angsuranLunas as $angsuran) {
            $jurnal = JurnalKas::firstOrCreate(
                ['tipe' => 'masuk', 'kategori' => 'pembayaran_angsuran', 'referensi_id' => $angsuran->id],
                [
                    'jumlah' => $angsuran->total_bayar,
                    'keterangan' => "Angsuran ke-{$angsuran->cicilan_ke} - {$pinjaman->anggota->nama}",
                    'tanggal' => $angsuran->tanggal_konfirmasi_bayar,
                    'created_by' => $benId,
                ]
            );

            if ($jurnal->wasRecentlyCreated) {
                $kas->increment('saldo_pinjaman', $angsuran->total_bayar);
            }
        }
    }

    /**
     * Beberapa topup historis agar segmen "Topup Saldo" di chart & riwayat kas terisi.
     */
    private function seedTopupKas(): void
    {
        $kas = KasKoperasi::firstOrCreate(['id' => 1], ['saldo_pinjaman' => 0]);
        $benId = $this->benId();

        $topups = [
            ['referensi_id' => 990001, 'jumlah' => 20_000_000, 'tanggal' => now()->subMonths(4)->day(2)],
            ['referensi_id' => 990002, 'jumlah' => 15_000_000, 'tanggal' => now()->subMonths(2)->day(2)],
        ];

        foreach ($topups as $t) {
            $jurnal = JurnalKas::firstOrCreate(
                ['tipe' => 'masuk', 'kategori' => 'topup_bulanan', 'referensi_id' => $t['referensi_id']],
                [
                    'jumlah' => $t['jumlah'],
                    'keterangan' => 'Topup saldo koperasi',
                    'tanggal' => $t['tanggal'],
                    'created_by' => $benId,
                ]
            );

            if ($jurnal->wasRecentlyCreated) {
                $kas->increment('saldo_pinjaman', $t['jumlah']);
            }
        }
    }

    private function benId(): ?int
    {
        return User::where('no_karyawan', 'BEN-000001')->value('id');
    }

    private function ketuaId(): ?int
    {
        return User::where('no_karyawan', 'KET-000001')->value('id');
    }

    /**
     * Generate jadwal angsuran dengan bunga menurun (declining balance).
     * Bunga dihitung dari sisa pokok tiap bulan.
     */
    private function generateJadwalAngsuran(Pinjaman $pinjaman, int $lunasSampaiCicilanKe = 0): void
    {
        // Kalau sudah pernah di-generate, skip (idempotent untuk firstOrCreate pattern)
        if ($pinjaman->angsuran()->exists()) {
            return;
        }

        $sisaPokok = (float) $pinjaman->nominal;
        $pokokPerBulan = $sisaPokok / $pinjaman->tenor_bulan;
        $persentaseBunga = (float) $pinjaman->persentase_bunga / 100;
        $benId = $this->benId();

        for ($cicilanKe = 1; $cicilanKe <= $pinjaman->tenor_bulan; $cicilanKe++) {
            $bunga = $sisaPokok * $persentaseBunga;
            $totalBayar = $pokokPerBulan + $bunga;

            $sudahLunas = $cicilanKe <= $lunasSampaiCicilanKe;

            Angsuran::create([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $cicilanKe,
                'nominal_pokok' => round($pokokPerBulan, 2),
                'nominal_bunga' => round($bunga, 2),
                'total_bayar' => round($totalBayar, 2),
                'status' => $sudahLunas ? 'lunas' : 'belum_bayar',
                'tanggal_jatuh_tempo' => $pinjaman->tanggal_pencairan->copy()->addMonths($cicilanKe),
                'tanggal_konfirmasi_bayar' => $sudahLunas
                    ? $pinjaman->tanggal_pencairan->copy()->addMonths($cicilanKe)
                    : null,
                'confirmed_by' => $sudahLunas ? $benId : null,
            ]);

            $sisaPokok -= $pokokPerBulan;
        }
    }
}
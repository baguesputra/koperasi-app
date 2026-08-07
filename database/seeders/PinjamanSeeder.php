<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\Pinjaman;
use Illuminate\Database\Seeder;

class PinjamanSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPinjamanBaru();
        $this->seedPinjamanAktifSebagianLunas();
        $this->seedPinjamanLunasSemua();
        $this->seedPinjamanSisaDuaAngsuran();
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
                'persentase_bunga' => 1.00,
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now()->subMonths(3),
                'tanggal_pencairan' => now()->subMonths(3)->addDays(3),
            ]
        );

        $this->generateJadwalAngsuran($pinjaman, lunasSampaiCicilanKe: 2);
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
                'persentase_bunga' => 1.00,
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now()->subMonths(8),
                'tanggal_pencairan' => now()->subMonths(8)->addDays(3),
            ]
        );

        $this->generateJadwalAngsuran($pinjaman, lunasSampaiCicilanKe: 6);
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
                'persentase_bunga' => 1.00,
                'sudah_pakai_privilege_reloan' => false,
                'tanggal_pengajuan' => now()->subMonths(10),
                'tanggal_pencairan' => now()->subMonths(10)->addDays(3),
            ]
        );

        // 10 dari 12 cicilan lunas, sisa 2 -> memenuhi syarat privilege reloan
        $this->generateJadwalAngsuran($pinjaman, lunasSampaiCicilanKe: 10);
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
            ]);

            $sisaPokok -= $pokokPerBulan;
        }
    }
}
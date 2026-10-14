<?php

namespace Tests\Feature;

use App\Laporan\LaporanRegistry;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanPendapatanBungaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_pendapatan_bunga_khusus_pinjaman_per_bulan(): void
    {
        $user = User::where('no_karyawan', 'BEN-000001')->firstOrFail();
        $this->actingAs($user);
        $lalu = (string) now()->subYear()->format('Y');

        $anggota = Anggota::create([
            'user_id' => null, 'no_anggota' => 'ANG-PB-001', 'nama' => 'Uji Bunga',
            'cabang' => 'Banjarmasin', 'unit_bisnis' => 'Ops', 'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2), 'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id, 'pengaju_user_id' => $user->id,
            'nominal' => 1_000_000, 'tenor_bulan' => 3, 'keperluan' => 'Uji',
            'persentase_bunga' => 1, 'status' => 'aktif',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);

        // Feb: 2 lunas (bunga 100rb + 50rb, pokok 250rb + 250rb).
        foreach ([[1, 100_000], [2, 50_000]] as [$ke, $bunga]) {
            Angsuran::create([
                'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => $ke,
                'nominal_pokok' => 250_000, 'nominal_bunga' => $bunga, 'total_bayar' => 250_000 + $bunga,
                'status' => 'lunas', 'tanggal_jatuh_tempo' => "{$lalu}-02-05",
                'tanggal_konfirmasi_bayar' => "{$lalu}-02-15",
            ]);
        }
        // Mar: 1 lunas via percepatan (bunga 25rb).
        $pp = PengajuanPercepatan::create([
            'pinjaman_id' => $pinjaman->id, 'tipe' => 'percepat',
            'tenor_lama' => 3, 'tenor_baru' => 2, 'status' => 'aktif',
            'keterangan' => 'Uji', 'tanggal_pengajuan' => "{$lalu}-03-01",
        ]);
        AngsuranPercepatan::create([
            'pengajuan_percepatan_id' => $pp->id, 'cicilan_ke' => 1,
            'nominal_pokok' => 100_000, 'nominal_bunga' => 25_000, 'total_bayar' => 125_000,
            'status' => 'lunas', 'tanggal_jatuh_tempo' => "{$lalu}-03-05",
            'tanggal_konfirmasi_bayar' => "{$lalu}-03-10",
        ]);

        // Kontrol negatif: belum lunas + lunas tanpa tanggal.
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 4,
            'nominal_pokok' => 250_000, 'nominal_bunga' => 999_000, 'total_bayar' => 1_249_000,
            'status' => 'belum_bayar', 'tanggal_jatuh_tempo' => "{$lalu}-02-05",
            'tanggal_konfirmasi_bayar' => "{$lalu}-02-15",
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 5,
            'nominal_pokok' => 250_000, 'nominal_bunga' => 888_000, 'total_bayar' => 1_138_000,
            'status' => 'lunas', 'tanggal_jatuh_tempo' => "{$lalu}-02-05",
            'tanggal_konfirmasi_bayar' => null,
        ]);

        $this->get(route('laporan.show', ['jenis' => 'pendapatan-bunga', 'tahun' => $lalu]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Laporan/Show')
                ->where('hasil.kolom', ['Periode', 'Angsuran Lunas', 'Total Pokok', 'Bunga Angsuran'])
                ->where('hasil.rows', function ($rows) use ($lalu) {
                    $cari = fn ($label) => collect($rows)->firstWhere(0, $label);

                    $feb = $cari("Februari {$lalu}");
                    $mar = $cari("Maret {$lalu}");

                    return $feb && $feb[1] == 2 && $feb[2] == 500000 && $feb[3] == 150000
                        && $mar && $mar[1] == 1 && $mar[2] == 100000 && $mar[3] == 25000;
                })
                ->where('hasil.totals.0', "TOTAL {$lalu}")
                ->where('hasil.totals.3', 175000)
                ->where('hasil.catatan', fn ($c) => str_contains($c, 'bunga bank tidak termasuk')));
    }

    public function test_judul_neraca_tanpa_kata_sederhana(): void
    {
        $this->assertSame('Laporan Neraca', LaporanRegistry::ambil('neraca')['judul']);
    }

    public function test_kolom_hitungan_tidak_diformat_rupiah(): void
    {
        $user = User::where('no_karyawan', 'BEN-000001')->firstOrFail();
        $this->actingAs($user);
        $lalu = (string) now()->subYear()->format('Y');

        $anggota = Anggota::create([
            'user_id' => null, 'no_anggota' => 'ANG-PB-002', 'nama' => 'Uji Hitung',
            'cabang' => 'Banjarmasin', 'unit_bisnis' => 'Ops', 'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2), 'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id, 'jenis' => 'wajib', 'jumlah' => 45_000,
            'bulan_periode' => "{$lalu}-02", 'tanggal_input' => "{$lalu}-02-10", 'input_by' => $user->id,
        ]);

        // Kolom hitungan (jumlah angsuran/penerima) di luar rataKanan agar tak berprefix Rp.
        $this->get(route('laporan.show', ['jenis' => 'pendapatan-bunga', 'tahun' => $lalu]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hasil.rataKanan', [2, 3]));

        $this->get(route('laporan.show', ['jenis' => 'setoran-bulanan', 'tahun' => $lalu]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Laporan/Show')
                ->where('hasil.rataKanan', [2, 3, 4])
                ->where('hasil.rows.0.1', 1));
    }
}

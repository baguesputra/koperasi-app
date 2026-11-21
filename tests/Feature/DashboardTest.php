<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pengurus_dapat_melihat_dashboard_dengan_saldo_bank(): void
    {
        $kas = KasKoperasi::first();
        $kas->update([
            'saldo_bank' => 25_000_000,
            'saldo_kas_kecil' => 5_000_000,
        ]);

        $this->masuk('ADM-000001');

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('stats.saldo_bank')
                ->where('stats.saldo_bank', 25_000_000)
                ->has('stats.saldo_kas_kecil')
                ->where('stats.saldo_kas_kecil', 5_000_000)
                ->has('stats.saldo_dana_sosial')
                ->missing('stats.total_keseluruhan')
                ->missing('stats.pendapatan_bunga_bulan_ini')
            );
    }

    public function test_dashboard_menghitung_saldo_dana_sosial_live_dari_jurnal_kas(): void
    {
        JurnalKas::where('kantong', 'dana_sosial')->delete();

        $kas = KasKoperasi::first();
        $kas->update(['saldo_dana_sosial' => 1_000_000]);

        $user = $this->masuk('ADM-000001');

        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'dana_sosial_bulanan',
            'kantong' => 'dana_sosial',
            'jumlah' => 2_500_000,
            'saldo_setelah' => 2_500_000,
            'keterangan' => 'Iuran dana sosial aktif',
            'tanggal' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        JurnalKas::create([
            'tipe' => 'keluar',
            'kategori' => 'pengeluaran_dana_sosial',
            'kantong' => 'dana_sosial',
            'jumlah' => 400_000,
            'saldo_setelah' => 2_100_000,
            'keterangan' => 'Santunan aktif',
            'tanggal' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $dibatalkan = JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'dana_sosial_bulanan',
            'kantong' => 'dana_sosial',
            'jumlah' => 900_000,
            'saldo_setelah' => 3_000_000,
            'keterangan' => 'Iuran dibatalkan',
            'tanggal' => now()->toDateString(),
            'created_by' => $user->id,
            'dibatalkan_pada' => now(),
            'dibatalkan_oleh' => $user->id,
            'alasan_batal' => 'Uji storno',
        ]);

        JurnalKas::create([
            'tipe' => 'keluar',
            'kategori' => 'dana_sosial_bulanan',
            'kantong' => 'dana_sosial',
            'jumlah' => 900_000,
            'saldo_setelah' => 2_100_000,
            'keterangan' => '[PEMBALIK] Uji storno',
            'tanggal' => now()->toDateString(),
            'created_by' => $user->id,
            'koreksi_dari_id' => $dibatalkan->id,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.saldo_dana_sosial', 2_100_000)
            );
    }

    public function test_anggota_biasa_dialihkan_ke_portal_dashboard(): void
    {
        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('portal.dashboard'));
    }
}

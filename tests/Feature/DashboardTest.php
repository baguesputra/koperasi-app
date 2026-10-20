<?php

namespace Tests\Feature;

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


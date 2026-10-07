<?php

namespace Tests\Feature;

use App\Models\SettingChipNominal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SettingChipNominalTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function sebagaiAdmin(): void
    {
        $this->masuk('ADM-000001');
        $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_default_santunan_tanpa_data_db(): void
    {
        SettingChipNominal::query()->delete();

        $this->assertSame(
            [100_000.0, 300_000.0, 500_000.0],
            SettingChipNominal::untuk('santunan')
        );
    }

    public function test_update_chip_menyimpan_urutan_dan_audit(): void
    {
        $this->sebagaiAdmin();

        $this->post(route('pengaturan.chip.update'), [
            'grup' => 'santunan',
            'daftar' => [500_000, 100_000, 300_000],
        ])->assertStatus(302);

        $this->assertSame(
            [500_000.0, 100_000.0, 300_000.0],
            SettingChipNominal::untuk('santunan')
        );
        $this->assertDatabaseHas('audit_log', ['aksi' => 'update_chip_nominal']);
    }

    public function test_update_chip_menolak_grup_asing_dan_anggota_biasa(): void
    {
        $this->sebagaiAdmin();
        $this->post(route('pengaturan.chip.update'), [
            'grup' => 'santunan',
            'daftar' => [],
        ])->assertSessionHasErrors('daftar');

        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);
        $this->post(route('pengaturan.chip.update'), [
            'grup' => 'santunan',
            'daftar' => [100_000],
        ])->assertForbidden();
    }

    public function test_share_inertia_memuat_semua_grup(): void
    {
        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);

        $this->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('chipNominal.santunan')
                ->has('chipNominal.pinjaman_usulan')
                ->has('chipNominal.limit_baru'));
    }
}

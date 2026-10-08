<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PengaturanTabTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->masuk('ADM-000001');
        $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    public static function legasi(): array
    {
        return [
            'bunga ke aturan-pinjaman' => ['bunga', 'aturan-pinjaman'],
            'limit ke aturan-pinjaman' => ['limit', 'aturan-pinjaman'],
            'tenor ke aturan-pinjaman' => ['tenor', 'aturan-pinjaman'],
            'simpanan ke dana-operasional' => ['simpanan', 'dana-operasional'],
            'kas ke dana-operasional' => ['kas', 'dana-operasional'],
            'chip ke dana-operasional' => ['chip', 'dana-operasional'],
        ];
    }

    #[DataProvider('legasi')]
    public function test_tab_legasi_redirect_ke_tab_gabungan(string $lama, string $baru): void
    {
        $this->get(route('pengaturan.index', ['tab' => $lama]))
            ->assertRedirect(route('pengaturan.index', ['tab' => $baru, 'section' => $lama]));
    }

    public function test_tab_aturan_pinjaman_memuat_tiga_setting(): void
    {
        $this->get(route('pengaturan.index', ['tab' => 'aturan-pinjaman']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Pengaturan/Index')
                ->where('tabAktif', 'aturan-pinjaman')
                ->has('bungaSaatIni')
                ->has('limitPinjaman')
                ->has('tabelTenor'));
    }

    public function test_tab_dana_operasional_memuat_tiga_setting(): void
    {
        $this->get(route('pengaturan.index', ['tab' => 'dana-operasional']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Pengaturan/Index')
                ->where('tabAktif', 'dana-operasional')
                ->has('settingSimpanan')
                ->has('settingKas')
                ->has('chipNominal'));
    }

    public function test_tab_asing_jatuh_ke_aturan_pinjaman(): void
    {
        $this->get(route('pengaturan.index', ['tab' => 'ngawur']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('tabAktif', 'aturan-pinjaman'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\SettingLimitPinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class LimitDinamisTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        EligibilitasPinjamanService::resetCache();
    }

    protected function tearDown(): void
    {
        EligibilitasPinjamanService::resetCache();
        parent::tearDown();
    }

    private function sebagaiAdmin(): void
    {
        $this->masuk('ADM-000001');
        $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_limit_mengikuti_rentang_bawaan(): void
    {
        $service = app(EligibilitasPinjamanService::class);

        $baru = $this->buatAnggota('TOP-910001', ['tanggal_jadi_anggota' => now()->subMonths(6)]);
        $this->assertEquals(1_000_000, $service->limitMaksimal($baru));

        $lama = $this->buatAnggota('TOP-910002', ['tanggal_jadi_anggota' => now()->subYears(6)]);
        $this->assertEquals(10_000_000, $service->limitMaksimal($lama));
    }

    public function test_admin_bisa_ubah_rentang_dan_nominal(): void
    {
        $this->sebagaiAdmin();
        $tigaSampai5 = SettingLimitPinjaman::where('kategori', 'tiga_sampai_5_tahun')->firstOrFail();
        $this->put(route('pengaturan.limit.update', $tigaSampai5), [
            'min_bulan' => 48,
            'maks_bulan' => 59,
            'limit_maksimal' => 7_000_000,
        ])->assertRedirect();

        $baris = SettingLimitPinjaman::where('kategori', 'satu_sampai_3_tahun')->firstOrFail();
        $this->put(route('pengaturan.limit.update', $baris), [
            'label' => 'Anggota 1-4 Tahun',
            'min_bulan' => 12,
            'maks_bulan' => 47,
            'limit_maksimal' => 6_000_000,
        ])->assertRedirect();
        EligibilitasPinjamanService::resetCache();

        $baris->refresh();
        $this->assertSame('Anggota 1-4 Tahun', $baris->label);
        $this->assertSame(47, $baris->maks_bulan);

        // 40 bulan masa → masuk rentang baru 12-47
        $anggota = $this->buatAnggota('TOP-910003', ['tanggal_jadi_anggota' => now()->subMonths(40)]);
        $this->assertEquals(6_000_000, app(EligibilitasPinjamanService::class)->limitMaksimal($anggota));
    }

    public function test_tambah_rentang_bertabrakan_ditolak(): void
    {
        $this->sebagaiAdmin();

        // Bertabrakan dengan 0-11 → ditolak
        $this->post(route('pengaturan.limit.store'), [
            'label' => 'Magang',
            'min_bulan' => 6,
            'maks_bulan' => 18,
            'limit_maksimal' => 2_000_000,
        ])->assertSessionHasErrors('min_bulan');

        $this->assertSame(4, SettingLimitPinjaman::count());
    }

    public function test_hapus_rentang(): void
    {
        $this->sebagaiAdmin();
        $baris = SettingLimitPinjaman::where('kategori', 'kurang_1_tahun')->firstOrFail();

        $this->delete(route('pengaturan.limit.destroy', $baris))->assertRedirect();
        $this->assertDatabaseMissing('setting_limit_pinjaman', ['id' => $baris->id]);
    }
}

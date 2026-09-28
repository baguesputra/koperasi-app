<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PengajuanLimit;
use App\Services\Pinjaman\PengajuanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PengajuanLimitTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ajukan($anggota, float $diminta = 8_000_000): PengajuanLimit
    {
        return app(PengajuanLimitService::class)->ajukan($anggota, $diminta, 'Butuh modal lebih besar.');
    }

    public function test_ajukan_harus_lebih_besar_dari_limit_kini(): void
    {
        $this->expectException(\RuntimeException::class);
        $anggota = $this->buatAnggota(); // limit kategori 1-3 thn = 5jt

        $this->ajukan($anggota, 3_000_000); // lebih kecil → gagal
    }

    public function test_tidak_boleh_dua_pengajuan_berjalan(): void
    {
        $this->expectException(\RuntimeException::class);
        $anggota = $this->buatAnggota();

        $this->ajukan($anggota, 8_000_000);
        $this->ajukan($anggota, 9_000_000);
    }

    public function test_bendahara_setujui_teruskan_ke_ketua_dengan_nominal_edit(): void
    {
        $anggota = $this->buatAnggota();
        $pengajuan = $this->ajukan($anggota, 10_000_000);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pengajuan-limit.approve', $pengajuan), [
            'catatan' => 'Layak diteruskan ke Ketua.',
            'limit_disetujui' => 8_000_000,
        ])->assertStatus(302);

        $pengajuan->refresh();
        $this->assertSame('approved_bendahara', $pengajuan->status);
        $this->assertEquals(8_000_000, (float) $pengajuan->limit_disetujui_bendahara);
        $this->assertNull($anggota->refresh()->limit_custom);
    }

    public function test_ketua_setujui_final_nominal_bisa_diubah_lagi(): void
    {
        $anggota = $this->buatAnggota();
        $pengajuan = $this->ajukan($anggota, 10_000_000);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pengajuan-limit.approve', $pengajuan), [
            'catatan' => 'Layak diteruskan ke Ketua.',
            'limit_disetujui' => 8_000_000,
        ])->assertStatus(302);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pengajuan-limit.approve', $pengajuan), [
            'catatan' => 'Final disetujui naik.',
            'limit_disetujui' => 9_000_000,
        ])->assertStatus(302);

        $pengajuan->refresh();
        $this->assertSame('disetujui', $pengajuan->status);
        $this->assertEquals(9_000_000, (float) $pengajuan->limit_disetujui);
        $this->assertEquals(9_000_000, (float) $anggota->refresh()->limit_custom);

        $this->assertTrue(
            AuditLog::where('aksi', 'setujui_pengajuan_limit')->where('keterangan', 'like', '%'.$anggota->nama.'%')->exists()
        );
    }

    public function test_bendahara_tolak_final_tidak_mengubah_limit(): void
    {
        $anggota = $this->buatAnggota();
        $pengajuan = $this->ajukan($anggota, 8_000_000);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pengajuan-limit.reject', $pengajuan), ['catatan' => 'Belum memadai.'])
            ->assertStatus(302);

        $this->assertSame('ditolak', $pengajuan->refresh()->status);
        $this->assertNull($anggota->refresh()->limit_custom);
    }

    public function test_approve_tanpa_nominal_ditolak_validasi(): void
    {
        $anggota = $this->buatAnggota();
        $pengajuan = $this->ajukan($anggota, 8_000_000);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pengajuan-limit.approve', $pengajuan), ['catatan' => 'Layak diteruskan ke Ketua.'])
            ->assertSessionHasErrors('limit_disetujui');

        $this->assertSame('diajukan', $pengajuan->refresh()->status);
    }

    public function test_ketua_tolak_tidak_mengubah_limit(): void
    {
        $anggota = $this->buatAnggota();
        $pengajuan = $this->ajukan($anggota, 8_000_000);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pengajuan-limit.approve', $pengajuan), [
            'catatan' => 'Diteruskan.',
            'limit_disetujui' => 7_000_000,
        ])->assertStatus(302);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pengajuan-limit.reject', $pengajuan), ['catatan' => 'Belum memadai.'])
            ->assertStatus(302);

        $this->assertSame('ditolak', $pengajuan->refresh()->status);
        $this->assertNull($anggota->refresh()->limit_custom);
    }
}

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

    public function test_setujui_menaikkan_limit_custom_dan_tercatat_audit(): void
    {
        $anggota = $this->buatAnggota();
        $pengajuan = $this->ajukan($anggota, 8_000_000);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pengajuan-limit.approve', $pengajuan), ['catatan' => 'Layak dinaikkan.'])
            ->assertStatus(302);

        $pengajuan->refresh();
        $this->assertSame('disetujui', $pengajuan->status);
        $this->assertEquals(8_000_000, (float) $anggota->refresh()->limit_custom);

        $this->assertTrue(
            AuditLog::where('aksi', 'setujui_pengajuan_limit')->where('keterangan', 'like', '%'.$anggota->nama.'%')->exists()
        );
    }

    public function test_tolak_tidak_mengubah_limit(): void
    {
        $anggota = $this->buatAnggota();
        $pengajuan = $this->ajukan($anggota, 8_000_000);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pengajuan-limit.reject', $pengajuan), ['catatan' => 'Belum memadai.'])
            ->assertStatus(302);

        $this->assertSame('ditolak', $pengajuan->refresh()->status);
        $this->assertNull($anggota->refresh()->limit_custom);
    }
}

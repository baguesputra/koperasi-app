<?php

namespace Tests\Feature;

use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PortalDashboardTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_portal_dashboard_menampilkan_pengajuan_ditolak_terbaru_dalam_30_hari(): void
    {
        $anggota = $this->buatAnggota('DSH-990001');

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_500_000,
            'tenor_bulan' => 6,
            'keperluan' => 'Kebutuhan mendesak',
            'persentase_bunga' => 1,
            'status' => 'ditolak',
            'catatan_ketua' => 'Limit belum mencukupi',
            'tanggal_pengajuan' => now()->subDays(5),
        ]);
        $pinjaman->timestamps = false;
        $pinjaman->updated_at = now()->subDays(5);
        $pinjaman->save();

        $this->actingAs($anggota->user);
        $res = $this->get(route('portal.dashboard'))->assertOk();

        $props = $res->viewData('page')['props'];
        $this->assertNotNull($props['pengajuanDitolak']);
        $this->assertSame($pinjaman->id, $props['pengajuanDitolak']['id']);
        $this->assertEqualsWithDelta(1_500_000, $props['pengajuanDitolak']['nominal'], 0.01);
        $this->assertSame('Limit belum mencukupi', $props['pengajuanDitolak']['catatan']);
    }

    public function test_portal_dashboard_mengabaikan_pengajuan_ditolak_lebih_dari_30_hari(): void
    {
        $anggota = $this->buatAnggota('DSH-990002');

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 6,
            'keperluan' => 'Kebutuhan lama',
            'persentase_bunga' => 1,
            'status' => 'ditolak',
            'catatan_ketua' => 'Ditolak tahun lalu',
            'tanggal_pengajuan' => now()->subDays(60),
        ]);
        $pinjaman->timestamps = false;
        $pinjaman->updated_at = now()->subDays(45);
        $pinjaman->save();

        $this->actingAs($anggota->user);
        $res = $this->get(route('portal.dashboard'))->assertOk();

        $props = $res->viewData('page')['props'];
        $this->assertNull($props['pengajuanDitolak']);
    }
}

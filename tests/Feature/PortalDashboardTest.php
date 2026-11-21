<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_masa_keanggotaan_menampilkan_tahun_bulan_dan_hari(): void
    {
        Carbon::setTestNow('2026-11-21 10:00:00');
        try {
            $this->assertSame('-', Anggota::formatLamaKeanggotaan(null));
            $this->assertSame('Baru bergabung', Anggota::formatLamaKeanggotaan(now()));
            $this->assertSame('12 hari', Anggota::formatLamaKeanggotaan(now()->subDays(12)));
            $this->assertSame('2 bulan 7 hari', Anggota::formatLamaKeanggotaan(Carbon::parse('2026-09-14')));
            $this->assertSame('1 tahun 2 bulan 5 hari', Anggota::formatLamaKeanggotaan(Carbon::parse('2025-09-16')));

            $anggota = $this->buatAnggota('DSH-990003', ['tanggal_jadi_anggota' => '2025-09-16']);

            $this->actingAs($anggota->user);
            $res = $this->get(route('portal.dashboard'))->assertOk();
            $this->assertSame('1 tahun 2 bulan 5 hari', $res->viewData('page')['props']['anggota']['lama_keanggotaan_label']);

            $resProfil = $this->get(route('portal.profil'))->assertOk();
            $this->assertSame('1 tahun 2 bulan 5 hari', $resProfil->viewData('page')['props']['anggota']['lama_keanggotaan_label']);

            $token = $anggota->user->createToken('uji')->plainTextToken;
            $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/v1/dashboard')
                ->assertOk()
                ->assertJsonPath('anggota.lama_keanggotaan_label', '1 tahun 2 bulan 5 hari');
        } finally {
            Carbon::setTestNow();
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Angsuran;
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

    public function test_dashboard_menampilkan_status_angsuran_aktif_dan_halaman_ajukan_memuat_saldo_serta_ketentuan_pinjaman(): void
    {
        $anggota = $this->buatAnggota('DSH-990004', [
            'tanggal_jadi_anggota' => now()->subYears(2),
        ]);

        // 1. Belum ada pinjaman aktif -> Dashboard tidak menerima tabelTenor, Ajukan menerima saldo & ketentuan tenor
        $this->actingAs($anggota->user);

        $resKosong = $this->get(route('portal.dashboard'))->assertOk();
        $propsKosong = $resKosong->viewData('page')['props'];
        $this->assertArrayNotHasKey('tabelTenor', $propsKosong);
        $this->assertSame(0, $propsKosong['pinjamanAktifCount']);

        $resAjukan = $this->get(route('portal.pinjaman.create'))->assertOk();
        $propsAjukan = $resAjukan->viewData('page')['props'];
        $this->assertGreaterThan(0, $propsAjukan['limitTersedia']);
        $this->assertGreaterThan(0, $propsAjukan['limitMaksimal']);
        $this->assertNotEmpty($propsAjukan['tabelTenor']);
        $this->assertArrayHasKey('bolehAjukanLimit', $propsAjukan);

        // 2. Memiliki pinjaman aktif -> pinjamanAktifList menyertakan sisa_angsuran, total_angsuran, & sisa_total_bayar
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_000_000,
            'nominal_disetujui' => 1_000_000,
            'tenor_bulan' => 2,
            'tenor_disetujui' => 2,
            'keperluan' => 'Modal kerja',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonth(),
            'tanggal_pencairan' => now()->subMonth(),
        ]);

        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 10_000,
            'total_bayar' => 510_000,
            'status' => 'lunas',
            'tanggal_jatuh_tempo' => now()->subDays(2),
            'tanggal_konfirmasi_bayar' => now()->subDays(2),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 2,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 5_000,
            'total_bayar' => 505_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->addMonth(),
        ]);

        $resAktif = $this->get(route('portal.dashboard'))->assertOk();
        $propsAktif = $resAktif->viewData('page')['props'];
        $this->assertSame(1, $propsAktif['pinjamanAktifCount']);
        $this->assertSame(1, $propsAktif['pinjamanAktifList'][0]['sisa_angsuran']);
        $this->assertSame(2, $propsAktif['pinjamanAktifList'][0]['total_angsuran']);
        $this->assertEqualsWithDelta(505_000, $propsAktif['pinjamanAktifList'][0]['sisa_total_bayar'], 0.01);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PengajuanAktivasi;
use App\Models\Simpanan;
use App\Services\Anggota\PengajuanAktivasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PengajuanAktivasiTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buatNonaktif(string $noKaryawan = 'TOP-910001')
    {
        return $this->buatAnggota($noKaryawan, ['status' => 'nonaktif', 'department' => 'Produksi']);
    }

    private function ajukan($anggota): PengajuanAktivasi
    {
        return app(PengajuanAktivasiService::class)->ajukan($anggota, true, true, $anggota->user_id);
    }

    public function test_nonaktif_dashboard_dialihkan_ke_landing_aktivasi(): void
    {
        $anggota = $this->buatNonaktif();
        $this->actingAs($anggota->user);

        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.aktivasi.landing'));
        $this->get(route('portal.pinjaman.create'))->assertRedirect(route('portal.aktivasi.landing'));
        $this->get(route('portal.aktivasi.landing'))->assertOk();
        $this->get(route('portal.aktivasi.create'))->assertOk();
    }

    public function test_aktif_tetap_ke_dashboard(): void
    {
        $anggota = $this->buatAnggota('TOP-910002');
        $this->actingAs($anggota->user);

        $this->get(route('portal.dashboard'))->assertOk();
        $this->get(route('portal.aktivasi.create'))->assertNotFound();
    }

    public function test_ajukan_wajib_centang_keduanya(): void
    {
        $anggota = $this->buatNonaktif('TOP-910003');
        $this->actingAs($anggota->user);

        $this->post(route('portal.aktivasi.store'), ['data_benar' => true])
            ->assertSessionHasErrors('setuju_syarat');

        $this->assertFalse(PengajuanAktivasi::where('anggota_id', $anggota->id)->exists());
    }

    public function test_ajukan_berhasil_dan_dedup_satu_berjalan(): void
    {
        $anggota = $this->buatNonaktif('TOP-910004');
        $this->actingAs($anggota->user);

        $this->post(route('portal.aktivasi.store'), ['data_benar' => true, 'setuju_syarat' => true])
            ->assertRedirect(route('portal.aktivasi.create'));

        $this->assertTrue(PengajuanAktivasi::where('anggota_id', $anggota->id)->where('status', 'diajukan')->exists());

        $this->post(route('portal.aktivasi.store'), ['data_benar' => true, 'setuju_syarat' => true])
            ->assertSessionHasErrors('pengajuan');
    }

    public function test_ketua_setujui_mengaktifkan_dan_catat_pokok(): void
    {
        $anggota = $this->buatNonaktif('TOP-910005');
        Simpanan::where('anggota_id', $anggota->id)->delete();
        $pengajuan = $this->ajukan($anggota);

        $this->masuk('KET-000001');
        $this->post(route('ketua.aktivasi.approve', $pengajuan), ['catatan' => 'Data valid, aktivasi disetujui.'])
            ->assertRedirect(route('ketua.aktivasi.index'));

        $this->assertSame('disetujui', $pengajuan->refresh()->status);
        $this->assertSame('aktif', $anggota->refresh()->status);
        $this->assertTrue(Simpanan::where('anggota_id', $anggota->id)->where('jenis', 'pokok')->exists());
        $this->assertTrue(AuditLog::where('aksi', 'aktivasi_disetujui')->exists());
    }

    public function test_ketua_tolak_boleh_ajukan_lagi(): void
    {
        $anggota = $this->buatNonaktif('TOP-910006');
        $pengajuan = $this->ajukan($anggota);

        $this->masuk('KET-000001');
        $this->post(route('ketua.aktivasi.reject', $pengajuan), ['catatan' => 'Data belum lengkap, perbaiki dulu.'])
            ->assertRedirect(route('ketua.aktivasi.index'));

        $this->assertSame('ditolak', $pengajuan->refresh()->status);
        $this->assertSame('nonaktif', $anggota->refresh()->status);

        $pengajuanBaru = $this->ajukan($anggota);
        $this->assertSame('diajukan', $pengajuanBaru->status);
    }

    public function test_resign_tidak_bisa_ajukan(): void
    {
        $this->expectException(\RuntimeException::class);
        $anggota = $this->buatAnggota('TOP-910007', ['status' => 'resign']);

        $this->ajukan($anggota);
    }

    public function test_halaman_ketua_aktivasi_mengirimkan_data_kepegawaian_dan_finansial_lengkap(): void
    {
        $anggota = $this->buatNonaktif('TOP-910008');
        $anggota->update([
            'no_karyawan' => 'TOP-910008',
            'no_ktp' => '3271234567890001',
            'tanggal_mulai_kerja' => now()->subYears(2)->subMonths(3),
        ]);
        $this->ajukan($anggota);

        $this->masuk('KET-000001');
        $res = $this->get(route('ketua.aktivasi.index'))->assertOk();
        $props = $res->viewData('page')['props'];

        $this->assertArrayHasKey('ketentuanFinansial', $props);
        $this->assertArrayHasKey('simpanan_pokok', $props['ketentuanFinansial']);
        $this->assertArrayHasKey('simpanan_wajib', $props['ketentuanFinansial']);
        $this->assertArrayHasKey('limit_awal', $props['ketentuanFinansial']);

        $this->assertNotEmpty($props['menunggu']);
        $item = collect($props['menunggu'])->firstWhere('anggota.no_karyawan', 'TOP-910008');
        $this->assertNotNull($item);
        $this->assertSame('3271234567890001', $item['anggota']['no_ktp']);
        $this->assertStringContainsString('tahun', $item['anggota']['masa_kerja']);
        $this->assertFalse($item['anggota']['is_reaktivasi']);
        $this->assertTrue($item['data_benar']);
        $this->assertTrue($item['setuju_syarat']);
    }
}

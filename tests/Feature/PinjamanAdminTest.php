<?php

namespace Tests\Feature;

use App\Jobs\KirimWaJob;
use App\Models\AuditLog;
use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PinjamanAdminTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ajukan($anggota): Pinjaman
    {
        $this->actingAs($anggota->user);
        $this->post(route('portal.pinjaman.store'), [
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Modal usaha sampingan',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
        ])->assertStatus(302);

        return Pinjaman::where('anggota_id', $anggota->id)->sole();
    }

    public function test_admin_meneruskan_ke_bendahara_dengan_wa_dan_audit(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukan($anggota);

        $this->masuk('ADM-000001');
        $this->get(route('admin.pinjaman.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('menungguTinjauan')
                ->has('riwayat'));

        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap, diteruskan.'])
            ->assertRedirect(route('admin.pinjaman.index'));

        $pinjaman->refresh();
        $this->assertSame('approved_admin', $pinjaman->status);
        $this->assertSame('Dokumen lengkap, diteruskan.', $pinjaman->catatan_admin);
        $this->assertTrue(AuditLog::where('aksi', 'pinjaman_teruskan_admin')->exists());

        Queue::assertPushed(KirimWaJob::class, fn (KirimWaJob $job) => $this->propertiWa($job)['event'] === 'pinjaman_diteruskan_admin');
    }

    public function test_admin_menolak_final(): void
    {
        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukan($anggota);

        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.reject', $pinjaman), ['catatan' => 'Dokumen tidak memenuhi syarat.'])
            ->assertRedirect(route('admin.pinjaman.index'));

        $pinjaman->refresh();
        $this->assertSame('ditolak', $pinjaman->status);
        $this->assertSame('Dokumen tidak memenuhi syarat.', $pinjaman->catatan_admin);

        $timeline = $pinjaman->getVerificationTimeline();
        $this->assertCount(4, $timeline);
        $this->assertSame('Verifikasi Admin', $timeline[1]['label']);
        $this->assertSame('rejected', $timeline[1]['status']);
    }

    public function test_bendahara_tidak_bisa_proses_sebelum_admin(): void
    {
        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukan($anggota);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Setuju.', 'nominal' => 1_000_000])
            ->assertSessionHasErrors('keputusan');
        $this->post(route('bendahara.pinjaman.reject', $pinjaman), ['catatan' => 'Ditolak bendahara.'])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame('diajukan', $pinjaman->refresh()->status);
    }

    public function test_ketua_tidak_bisa_proses_sebelum_bendahara(): void
    {
        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukan($anggota);

        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Bendahara.'])
            ->assertRedirect();

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 1_000_000])
            ->assertSessionHasErrors('keputusan');
        $this->post(route('ketua.pinjaman.reject', $pinjaman), ['catatan' => 'Ditolak ketua.'])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame('approved_admin', $pinjaman->refresh()->status);
    }

    public function test_bendahara_dan_anggota_tidak_bisa_akses_halaman_admin(): void
    {
        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukan($anggota);

        $this->masuk('BEN-000001');
        $this->get(route('admin.pinjaman.index'))->assertForbidden();

        $this->actingAs($anggota->user);
        $this->get(route('admin.pinjaman.index'))->assertForbidden();
    }

    public function test_post_pinjaman_melebihi_limit_tersedia_gagal_validasi(): void
    {
        $anggota = $this->buatAnggota(); // limit default kategori 1-3 thn = 5jt
        $this->actingAs($anggota->user);

        $response = $this->post(route('portal.pinjaman.store'), [
            'nominal' => 8_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Mencoba bypass nominal lebih dari limit tersedia',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
        ]);

        $response->assertSessionHasErrors(['nominal', 'pengajuan']);
        $this->assertDatabaseMissing('pinjaman', [
            'anggota_id' => $anggota->id,
            'nominal' => 8_000_000,
        ]);
    }

    public function test_post_pinjaman_melebihi_kebijakan_koperasi_gagal_validasi(): void
    {
        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);

        $response = $this->post(route('portal.pinjaman.store'), [
            'nominal' => 15_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Mencoba bypass nominal lebih dari plafon tertinggi koperasi',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
        ]);

        $response->assertSessionHasErrors(['nominal', 'pengajuan']);
        $this->assertDatabaseMissing('pinjaman', [
            'anggota_id' => $anggota->id,
            'nominal' => 15_000_000,
        ]);
    }
}

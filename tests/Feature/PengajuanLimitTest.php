<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PengajuanLimit;
use App\Models\Pinjaman;
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

    public function test_ajukan_tidak_boleh_melebihi_batas_kebijakan_koperasi(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nominal yang diajukan melebihi batas kebijakan koperasi.');

        $anggota = $this->buatAnggota(); // limit kategori 1-3 thn = 5jt
        $this->ajukan($anggota, 15_000_000); // 15jt > 10jt plafon kebijakan → gagal
    }

    public function test_post_portal_pengajuan_limit_melebihi_kebijakan_gagal_validasi(): void
    {
        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);

        $response = $this->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 15_000_000,
            'keterangan' => 'Mencoba submit nominal lebih besar dari kebijakan.',
        ]);

        $response->assertSessionHasErrors([
            'limit_diminta' => 'Nominal yang diajukan melebihi batas kebijakan koperasi.',
        ]);

        $this->assertDatabaseMissing('pengajuan_limit', [
            'anggota_id' => $anggota->id,
            'limit_diminta' => 15_000_000,
        ]);
    }

    public function test_anggota_dengan_pinjaman_diproses_tidak_bisa_akses_atau_mengirim_pengajuan_limit(): void
    {
        $anggota = $this->buatAnggota();
        Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'persentase_bunga' => 1,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);
        $this->actingAs($anggota->user);

        $this->get(route('portal.pengajuan-limit.create'))->assertForbidden();
        $this->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 8_000_000,
            'keterangan' => 'Menaikkan limit untuk kebutuhan modal.',
        ])->assertSessionHasErrors(['limit_diminta' => 'Pengajuan pinjaman sedang diproses.']);

        $this->assertDatabaseMissing('pengajuan_limit', ['anggota_id' => $anggota->id]);
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

    public function test_anggota_dengan_pengajuan_limit_diproses_tidak_bisa_akses_atau_mengirim_pengajuan_pinjaman(): void
    {
        $anggota = $this->buatAnggota();
        PengajuanLimit::create([
            'anggota_id' => $anggota->id,
            'limit_saat_ini' => 5_000_000,
            'limit_diminta' => 8_000_000,
            'keterangan' => 'Menaikkan limit untuk kebutuhan modal.',
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);
        $this->actingAs($anggota->user);

        $this->get(route('portal.pinjaman.create'))->assertForbidden();
        $this->post(route('portal.pinjaman.store'), [
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Kebutuhan mendesak keluarga.',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => '1',
        ])->assertSessionHasErrors(['pengajuan' => 'Pengajuan kenaikan limit sedang diproses.']);

        $this->assertDatabaseMissing('pinjaman', ['anggota_id' => $anggota->id]);
    }

    public function test_anggota_dengan_pinjaman_diproses_tidak_bisa_mengajukan_pinjaman_lagi(): void
    {
        $anggota = $this->buatAnggota();
        Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'persentase_bunga' => 1,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);
        $this->actingAs($anggota->user);

        $this->get(route('portal.pinjaman.create'))->assertForbidden();
        $this->post(route('portal.pinjaman.store'), [
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Kebutuhan tambahan.',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => '1',
        ])->assertSessionHasErrors(['pengajuan' => 'Pengajuan pinjaman sedang diproses.']);
    }

    public function test_limit_yang_mencapai_plafon_koperasi_terkunci_dan_tidak_dapat_diajukan(): void
    {
        $anggota = $this->buatAnggota('TOP-900002', ['limit_custom' => 10_000_000]);
        $this->actingAs($anggota->user);

        $response = $this->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 10_000_000,
            'keterangan' => 'Mencoba submit padahal sudah maksimal.',
        ]);

        $response->assertSessionHasErrors([
            'limit_diminta' => 'Limit Anda telah mencapai batas maksimal kebijakan koperasi (Rp 10.000.000).',
        ]);
    }

    public function test_setelah_limit_disetujui_anggota_wajib_memanfaatkan_limit_terlebih_dahulu(): void
    {
        $anggota = $this->buatAnggota('TOP-900003');
        $pengajuan = $this->ajukan($anggota, 8_000_000);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pengajuan-limit.approve', $pengajuan), [
            'catatan' => 'Diteruskan ke Ketua.',
            'limit_disetujui' => 8_000_000,
        ])->assertStatus(302);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pengajuan-limit.approve', $pengajuan), [
            'catatan' => 'Disetujui.',
            'limit_disetujui' => 8_000_000,
        ])->assertStatus(302);

        // Setelah disetujui, anggota mencoba mengajukan limit lagi tanpa mengambil pinjaman baru
        $this->actingAs($anggota->user);
        $response = $this->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 9_000_000,
            'keterangan' => 'Minta naik lagi langsung.',
        ]);

        $response->assertSessionHasErrors([
            'limit_diminta' => 'Anda telah mendapatkan kenaikan limit menjadi Rp 8.000.000. Manfaatkan limit ini untuk pinjaman terlebih dahulu sebelum mengajukan kenaikan berikutnya.',
        ]);
    }

    public function test_pinjaman_aktif_berjalan_lebih_dari_dua_angsuran_mengunci_pengajuan_limit(): void
    {
        $anggota = $this->buatAnggota('TOP-900004');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 3_000_000,
            'tenor_bulan' => 6,
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(1),
            'disetujui_pada' => now()->subMonths(1),
        ]);

        for ($i = 1; $i <= 6; $i++) {
            \App\Models\Angsuran::create([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $i,
                'nominal_pokok' => 500_000,
                'nominal_bunga' => 30_000,
                'total_bayar' => 530_000,
                'tanggal_jatuh_tempo' => now()->addMonths($i),
                'status' => 'belum_bayar',
            ]);
        }

        $this->actingAs($anggota->user);
        $response = $this->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 8_000_000,
            'keterangan' => 'Mencoba tambah limit saat sisa 6 angsuran.',
        ]);

        $response->assertSessionHasErrors([
            'limit_diminta' => 'Pinjaman sebelumnya belum lunas. Kenaikan limit baru dapat diajukan setelah pinjaman berjalan tersisa maksimal 2 angsuran atau telah lunas.',
        ]);
    }

    public function test_pinjaman_aktif_sisa_dua_angsuran_membuka_kembali_pengajuan_limit(): void
    {
        $anggota = $this->buatAnggota('TOP-900005');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 3_000_000,
            'tenor_bulan' => 6,
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(4),
            'disetujui_pada' => now()->subMonths(4),
        ]);

        for ($i = 1; $i <= 4; $i++) {
            \App\Models\Angsuran::create([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $i,
                'nominal_pokok' => 500_000,
                'nominal_bunga' => 30_000,
                'total_bayar' => 530_000,
                'tanggal_jatuh_tempo' => now()->subMonths(4 - $i),
                'status' => 'lunas',
            ]);
        }
        for ($i = 5; $i <= 6; $i++) {
            \App\Models\Angsuran::create([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $i,
                'nominal_pokok' => 500_000,
                'nominal_bunga' => 30_000,
                'total_bayar' => 530_000,
                'tanggal_jatuh_tempo' => now()->addMonths($i - 4),
                'status' => 'belum_bayar',
            ]);
        }

        $this->actingAs($anggota->user);
        $response = $this->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 8_000_000,
            'keterangan' => 'Sisa 2 angsuran dan ingin ajukan limit.',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pengajuan_limit', [
            'anggota_id' => $anggota->id,
            'limit_diminta' => 8_000_000,
            'status' => 'diajukan',
        ]);
    }

    public function test_api_pengajuan_limit_mengembalikan_flag_dan_status_eligibilitas(): void
    {
        $anggota = $this->buatAnggota('TOP-900006');
        \Laravel\Sanctum\Sanctum::actingAs($anggota->user);

        $response = $this->getJson(route('api.pengajuan-limit.index'));

        $response->assertOk();
        $response->assertJson([
            'boleh_tambah_limit' => true,
            'status_limit_badge' => 'tersedia',
            'state_limit_label' => 'Ajukan kenaikan limit',
        ]);
    }
}

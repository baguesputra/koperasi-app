<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\SettingKas;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class KasPaguOperasionalTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ajukan($anggota, float $nominal = 1_000_000, int $tenor = 3): Pinjaman
    {
        $this->actingAs($anggota->user);
        $this->post(route('portal.pinjaman.store'), [
            'nominal' => $nominal,
            'tenor_bulan' => $tenor,
            'keperluan' => 'Modal usaha sampingan',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
        ])->assertStatus(302);

        return Pinjaman::where('anggota_id', $anggota->id)->sole();
    }

    public function test_saldo_operasional_gabungan_tiga_kantong(): void
    {
        $kas = KasKoperasi::first();
        $layak = app(JurnalKasService::class)->saldoOperasional($kas);

        $this->assertEquals(
            (float) $kas->saldo_pinjaman + (float) $kas->saldo_dana_sosial + (float) $kas->saldo_simpanan,
            $layak
        );
    }

    public function test_sisa_pagu_mengikuti_config(): void
    {
        $info = app(JurnalKasService::class)->sisaPaguBulan();

        $this->assertEquals(50_000_000, $info['pagu']);
        $this->assertEquals(5_000_000, $info['cadangan']);
        $this->assertEquals(0, $info['sudah_cair']);
        $this->assertEquals(min(50_000_000, $info['saldo_operasional'] - 5_000_000), $info['layak']);
    }

    public function test_cair_ditolak_bila_melebihi_pagu(): void
    {
        SettingKas::updateOrCreate(['kunci' => SettingKas::PAGU], ['label' => 'Pagu', 'nominal' => 500_000]);

        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukan($anggota);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Setuju, data lengkap.', 'nominal' => 1_000_000])
            ->assertStatus(302);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 1_000_000])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame('approved_bendahara', $pinjaman->refresh()->status);
        $this->assertSame(0, JurnalKas::where('kategori', 'pencairan_pinjaman')->where('referensi_id', $pinjaman->id)->count());
    }

    public function test_cair_ditolak_bila_sisa_di_bawah_cadangan_sosial(): void
    {
        $kas = KasKoperasi::first();
        $kas->update(['saldo_pinjaman' => 4_000_000, 'saldo_dana_sosial' => 1_000_000, 'saldo_simpanan' => 500_000]);

        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukan($anggota);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Setuju, data lengkap.', 'nominal' => 1_000_000])
            ->assertStatus(302);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 1_000_000])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame('approved_bendahara', $pinjaman->refresh()->status);
    }

    public function test_update_pagu_via_pengaturan_tercatat_audit(): void
    {
        $this->masuk('ADM-000001');
        $this->withSession(['auth.password_confirmed_at' => time()]);
        $setting = SettingKas::where('kunci', SettingKas::PAGU)->firstOrFail();

        $this->post(route('pengaturan.kas.update', $setting), ['nominal' => 75_000_000])
            ->assertStatus(302);

        $this->assertEquals(75_000_000, (float) $setting->refresh()->nominal);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'update_setting_kas']);
        $this->assertEquals(75_000_000, app(JurnalKasService::class)->sisaPaguBulan()['pagu']);
    }
}

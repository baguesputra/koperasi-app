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

    public function test_saldo_operasional_bank_dan_kas_kecil(): void
    {
        $kas = KasKoperasi::first();
        $layak = app(JurnalKasService::class)->saldoOperasional($kas);

        $this->assertEquals(
            (float) $kas->saldo_bank + (float) $kas->saldo_kas_kecil,
            $layak
        );
    }

    public function test_sudah_cair_tercatat_di_jurnal_dan_mengurangi_bank(): void
    {
        $bankSebelum = (float) KasKoperasi::first()->saldo_bank;
        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukan($anggota, 1_000_000);

        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Bendahara.'])
            ->assertStatus(302);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Setuju, data lengkap.', 'nominal' => 1_000_000])
            ->assertStatus(302);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Disetujui Ketua.', 'nominal' => 1_000_000])
            ->assertStatus(302);

        $this->assertEquals($bankSebelum - 1_000_000, (float) KasKoperasi::first()->saldo_bank);
        $this->assertSame(1, JurnalKas::where('kategori', 'pencairan_pinjaman')->where('referensi_id', $pinjaman->id)->count());
    }

    public function test_cair_ditolak_bila_melebihi_saldo_bank(): void
    {
        $kas = KasKoperasi::first();
        $kas->update(['saldo_bank' => 500_000]);
        $kas->refresh();

        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukan($anggota);

        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Bendahara.'])
            ->assertStatus(302);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Setuju, data lengkap.', 'nominal' => 1_000_000])
            ->assertStatus(302);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 1_000_000])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame('approved_bendahara', $pinjaman->refresh()->status);
        $this->assertSame(0, JurnalKas::where('kategori', 'pencairan_pinjaman')->where('referensi_id', $pinjaman->id)->count());
    }

    public function test_update_pagu_kas_kecil_via_pengaturan_tercatat_audit(): void
    {
        $this->masuk('ADM-000001');
        $this->withSession(['auth.password_confirmed_at' => time()]);
        $setting = SettingKas::where('kunci', SettingKas::CADANGAN)->firstOrFail();

        $this->post(route('pengaturan.kas.update', $setting), ['nominal' => 7_500_000])
            ->assertStatus(302);

        $this->assertEquals(7_500_000, (float) $setting->refresh()->nominal);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'update_setting_kas']);
    }

    public function test_rekonsiliasi_ok_setelah_seed(): void
    {
        $this->artisan('kas:rekonsiliasi')->assertSuccessful();
    }
}

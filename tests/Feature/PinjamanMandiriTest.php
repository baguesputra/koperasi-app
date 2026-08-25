<?php

namespace Tests\Feature;

use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PinjamanMandiriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function login(string $noKaryawan): User
    {
        $user = User::where('no_karyawan', $noKaryawan)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    private function ajukan(array $override = []): TestResponse
    {
        return $this->post(route('portal.pinjaman.store'), array_merge([
            'nominal' => 500_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Keperluan pribadi koperasi',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => 'Pengurus Koperasi',
            'persetujuan' => true,
        ], $override));
    }

    public function test_bendahara_mengajukan_langsung_approved_bendahara(): void
    {
        $bendahara = $this->login('BEN-000001');

        $this->ajukan()->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('pinjaman', [
            'pengaju_user_id' => $bendahara->id,
            'status' => 'approved_bendahara',
            'cair_oleh_bendahara' => false,
        ]);

        $pinjaman = Pinjaman::where('pengaju_user_id', $bendahara->id)->first();
        $this->assertStringContainsString(
            'Diajukan mandiri oleh Bendahara',
            $pinjaman->catatan_bendahara
        );
    }

    public function test_bendahara_mengajukan_muncul_di_antrean_ketua(): void
    {
        $bendahara = $this->login('BEN-000001');
        $this->ajukan();
        $pinjaman = Pinjaman::where('pengaju_user_id', $bendahara->id)->first();

        $this->login('KET-000001');
        $response = $this->get(route('ketua.pinjaman.index'));
        $response->assertOk();

        $masukAntreanKetua = Pinjaman::where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', false)
            ->where('id', $pinjaman->id)
            ->exists();
        $this->assertTrue($masukAntreanKetua);
    }

    public function test_ketua_mengajukan_menunggu_bendahara_lalu_cair_oleh_bendahara(): void
    {
        $ketua = $this->login('KET-000001');

        $this->ajukan()->assertRedirect(route('portal.dashboard'));
        $pinjaman = Pinjaman::where('pengaju_user_id', $ketua->id)->first();

        $this->assertDatabaseHas('pinjaman', [
            'id' => $pinjaman->id,
            'status' => 'diajukan',
            'cair_oleh_bendahara' => true,
        ]);

        // Bendahara menyetujui
        $this->login('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Data lengkap.'])
            ->assertRedirect();

        $pinjaman->refresh();
        $this->assertEquals('approved_bendahara', $pinjaman->status);
        $this->assertTrue($pinjaman->cair_oleh_bendahara);

        // Bendahara mencairkan (bukan Ketua)
        $this->post(route('bendahara.pinjaman.cair', $pinjaman), ['catatan' => 'Dana dicairkan.'])
            ->assertRedirect();

        $pinjaman->refresh();
        $this->assertEquals('aktif', $pinjaman->status);
        $this->assertNotNull($pinjaman->tanggal_pencairan);
        $this->assertCount($pinjaman->tenor_bulan, $pinjaman->angsuran()->get());
    }

    public function test_ketua_tidak_melihat_pinjamannya_di_antrean_approval_sendiri(): void
    {
        $ketua = $this->login('KET-000001');
        $this->ajukan();
        $pinjaman = Pinjaman::where('pengaju_user_id', $ketua->id)->first();

        $this->login('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Data lengkap.']);

        // Pinjaman milik Ketua (cair_oleh_bendahara=true) tidak boleh muncul di antrean
        // persetujuan Ketua, supaya Ketua tidak self-approve/cair.
        $masukAntreanKetua = Pinjaman::where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', false)
            ->where('id', $pinjaman->id)
            ->exists();
        $this->assertFalse($masukAntreanKetua);

        // Sebaliknya muncul di antrean pencairan Bendahara
        $masukAntreanCairBendahara = Pinjaman::where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', true)
            ->where('id', $pinjaman->id)
            ->exists();
        $this->assertTrue($masukAntreanCairBendahara);
    }
}

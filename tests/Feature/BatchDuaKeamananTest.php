<?php

namespace Tests\Feature;

use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

// ponytail: regresi audit batch-2 (self-approval, PIN, token); tambah kasus bila kritis baru muncul.
class BatchDuaKeamananTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_ditolak_memproses_pengajuan_sendiri(): void
    {
        $admin = $this->masuk('ADM-000001');
        $anggota = $this->buatAnggota('TOP-900700');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $admin->id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'persentase_bunga' => 1.5,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Teruskan sendiri.'])
            ->assertSessionHasErrors(['keputusan']);
        $this->assertSame('diajukan', $pinjaman->refresh()->status);
    }

    public function test_bendahara_ditolak_mencairkan_pengajuan_sendiri(): void
    {
        $bendahara = $this->masuk('BEN-000001');
        $anggota = $this->buatAnggota('TOP-900701');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $bendahara->id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'persentase_bunga' => 1.5,
            'status' => 'approved_admin',
            'tanggal_pengajuan' => now(),
        ]);

        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Setuju sendiri.', 'nominal' => 1_000_000])
            ->assertSessionHasErrors(['keputusan']);
        $this->assertSame('approved_admin', $pinjaman->refresh()->status);
    }

    public function test_ubah_pin_tanpa_old_pin_ditolak(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('112233')]);

        $this->actingAs($user)->post(route('security.pin.setup'), [
            'pin' => '445566',
            'pin_confirmation' => '445566',
        ])->assertSessionHasErrors(['old_pin']);

        $this->assertTrue(Hash::check('112233', $user->refresh()->pin));
    }

    public function test_ubah_pin_dengan_old_pin_salah_ditolak(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('112233')]);

        $this->actingAs($user)->postJson(route('security.pin.setup'), [
            'pin' => '445566',
            'pin_confirmation' => '445566',
            'old_pin' => '000000',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('112233', $user->refresh()->pin));
    }

    public function test_pin_sama_semua_digit_ditolak(): void
    {
        $user = User::factory()->create(['pin' => null]);

        $this->actingAs($user)->post(route('security.pin.setup'), [
            'pin' => '111111',
            'pin_confirmation' => '111111',
        ])->assertSessionHasErrors(['pin']);
    }

    public function test_lockout_tetap_ditolak_setelah_login_ulang(): void
    {
        $user = User::factory()->create([
            'pin' => Hash::make('112233'),
            'pin_failed_attempts' => 3,
            'pin_locked_until' => now()->addMinutes(15),
        ]);

        $this->actingAs($user)->postJson(route('security.pin.verify-unlock'), ['pin' => '112233'])
            ->assertStatus(423);
    }

    public function test_nonaktifkan_akun_mencabut_token_api(): void
    {
        $this->masuk('ADM-000001');
        session(['auth.password_confirmed_at' => time()]);
        $target = $this->buatAnggota('TOP-900702')->user;
        $target->createToken('uji');

        $this->post(route('pengaturan.pengguna.toggle-status', $target->id))
            ->assertSessionHasNoErrors();
        $this->assertSame('nonaktif', $target->fresh()->status);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
    }
}

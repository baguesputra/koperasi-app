<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SecurityPinTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    public function test_user_can_setup_pin(): void
    {
        $user = User::factory()->create(['pin' => null]);
        $this->assertFalse($user->hasPin());

        $response = $this->actingAs($user)->postJson(route('security.pin.setup'), [
            'pin' => '123456',
            'pin_confirmation' => '123456',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertTrue($user->hasPin());
        $this->assertTrue(Hash::check('123456', $user->pin));
    }

    public function test_user_can_unlock_screen_with_correct_pin(): void
    {
        $user = User::factory()->create([
            'pin' => Hash::make('654321'),
            'pin_failed_attempts' => 1,
        ]);

        $response = $this->actingAs($user)->postJson(route('security.pin.verify-unlock'), [
            'pin' => '654321',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertEquals(0, $user->pin_failed_attempts);
    }

    public function test_user_fails_unlock_with_incorrect_pin_and_decrements_attempts(): void
    {
        $user = User::factory()->create([
            'pin' => Hash::make('112233'),
            'pin_failed_attempts' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('security.pin.verify-unlock'), [
            'pin' => '999999',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'failed',
            'remaining' => 2,
        ]);

        $user->refresh();
        $this->assertEquals(1, $user->pin_failed_attempts);
    }

    public function test_user_locked_out_after_three_failed_attempts(): void
    {
        $user = User::factory()->create([
            'pin' => Hash::make('112233'),
            'pin_failed_attempts' => 2,
        ]);

        $response = $this->actingAs($user)->postJson(route('security.pin.verify-unlock'), [
            'pin' => '999999',
        ]);

        $response->assertStatus(423);
        $response->assertJson([
            'status' => 'locked_out',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'security_alert_pin_lockout',
        ]);
    }

    public function test_admin_can_reset_user_pin(): void
    {
        Permission::firstOrCreate(['name' => 'user.kelola']);

        $admin = User::factory()->create();
        $admin->givePermissionTo('user.kelola');

        $user = User::factory()->create([
            'pin' => Hash::make('123456'),
            'pin_failed_attempts' => 2,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('pengaturan.pengguna.reset-pin', $user->id));

        $response->assertRedirect();
        $user->refresh();
        $this->assertFalse($user->hasPin());
        $this->assertNull($user->pin);
        $this->assertEquals(0, $user->pin_failed_attempts);

        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'reset_pin_pengguna',
        ]);
    }

    public function test_member_loan_submission_validates_pin(): void
    {
        $this->seed();
        $anggota = $this->buatAnggota('KRY-888001');
        $anggota->user->update(['pin' => Hash::make('888999')]);

        // Coba ajukan dengan PIN salah
        $response = $this->actingAs($anggota->user)->post(route('portal.pinjaman.store'), [
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Kebutuhan mendesak',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
            'pin' => '000000',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertDatabaseMissing('pinjaman', ['anggota_id' => $anggota->id]);

        // Coba ajukan dengan PIN benar
        $responseSuccess = $this->actingAs($anggota->user)->post(route('portal.pinjaman.store'), [
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Kebutuhan mendesak',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
            'pin' => '888999',
        ]);

        $responseSuccess->assertRedirect(route('portal.dashboard'));
        $this->assertDatabaseHas('pinjaman', [
            'anggota_id' => $anggota->id,
            'nominal' => 1_000_000,
        ]);
    }
}

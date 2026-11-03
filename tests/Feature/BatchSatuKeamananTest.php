<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

// ponytail: regresi audit batch-1; tambah kasus bila kritis baru muncul.
class BatchSatuKeamananTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_ditolak_menetapkan_super_admin(): void
    {
        $this->masuk('ADM-000001');
        session(['auth.password_confirmed_at' => time()]);

        $this->post(route('pengaturan.pengguna.store'), [
            'name' => 'Nakal',
            'no_karyawan' => 'ADM-000099',
            'role' => 'super_admin',
        ])->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', ['no_karyawan' => 'ADM-000099']);
    }

    public function test_admin_ditolak_menaikkan_role_sendiri_ke_super_admin(): void
    {
        $admin = $this->masuk('ADM-000001');
        session(['auth.password_confirmed_at' => time()]);

        $this->put(route('pengaturan.pengguna.update', $admin->id), [
            'name' => $admin->name,
            'no_karyawan' => $admin->no_karyawan,
            'role' => 'super_admin',
            'status' => 'aktif',
        ])->assertSessionHasErrors(['role']);
        $this->assertFalse($admin->fresh()->hasRole('super_admin'));
    }

    public function test_reset_password_dan_pin_akun_root_ditolak(): void
    {
        $this->masuk('ADM-000001');
        session(['auth.password_confirmed_at' => time()]);
        $root = User::where('no_karyawan', 'SPADM-000001')->firstOrFail();

        $this->post(route('pengaturan.pengguna.reset-password', $root->id), ['password' => 'RahasiaBaru123!'])
            ->assertSessionHasErrors(['pengguna']);
        $this->post(route('pengaturan.pengguna.reset-pin', $root->id))
            ->assertSessionHasErrors(['pengguna']);
    }

    public function test_reset_password_mencabut_token_api(): void
    {
        $this->masuk('ADM-000001');
        session(['auth.password_confirmed_at' => time()]);
        $target = User::where('no_karyawan', 'TOP-100001')->firstOrFail();
        $target->createToken('uji');

        $this->post(route('pengaturan.pengguna.reset-password', $target->id), ['password' => 'RahasiaBaru123!'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
    }
}

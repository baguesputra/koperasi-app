<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_profil_tampil_read_only(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('pengguna', fn ($p) => $p
                    ->where('no_karyawan', $user->no_karyawan)
                    ->etc()));
    }

    public function test_jalur_ubah_hapus_profil_dan_password_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', ['name' => 'X'])->assertStatus(405);
        $this->actingAs($user)->delete('/profile')->assertStatus(405);
        $this->actingAs($user)->put('/password', ['password' => 'x'])->assertNotFound();
        $this->actingAs($user)->get('/ganti-password-wajib')->assertNotFound();
    }

    public function test_halaman_profil_pengurus_menampilkan_ringkasan_dan_roles(): void
    {
        $this->seed();
        $admin = User::where('no_karyawan', 'ADM-000001')->first();

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('pengguna.roles')
                ->has('ringkasan')
            );
    }
}

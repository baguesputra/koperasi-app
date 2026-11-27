<?php

namespace Tests\Feature;

use App\Models\RekeningAnggota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_profil_tampil_read_only(): void
    {
        $user = User::factory()->create(['no_karyawan' => 'PROF-000001']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('pengguna', fn ($p) => $p
                    ->where('no_karyawan', $user->no_karyawan)
                    ->where('anggota', null)
                    ->etc())
                ->where('ringkasan', null));
    }

    public function test_jalur_ubah_hapus_profil_dan_password_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', ['name' => 'X'])->assertStatus(405);
        $this->actingAs($user)->delete('/profile')->assertStatus(405);
        $this->actingAs($user)->put('/password', ['password' => 'x'])->assertNotFound();
        $this->actingAs($user)->get('/ganti-password-wajib')->assertNotFound();
    }

    public function test_halaman_profil_pengurus_menampilkan_ringkasan_keuangan_rekening_dan_roles(): void
    {
        $this->seed();
        $admin = User::where('no_karyawan', 'ADM-000001')->firstOrFail();

        if ($admin->anggota) {
            RekeningAnggota::create([
                'anggota_id' => $admin->anggota->id,
                'nama_bank' => 'BCA',
                'no_rekening' => '1234567890',
                'atas_nama' => $admin->name,
                'is_default' => true,
            ]);
        }

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('pengguna.roles')
                ->has('pengguna.anggota.lama_keanggotaan_label')
                ->has('pengguna.anggota.rekening', 1)
                ->has('ringkasan', fn ($r) => $r
                    ->has('simpanan_pokok')
                    ->has('simpanan_wajib')
                    ->has('simpanan_total')
                    ->has('tabungan')
                    ->has('sisa_tagihan_pinjaman')
                    ->has('sisa_cicilan_pinjaman')
                    ->has('plafon_pinjaman')
                    ->has('antrean_aktif')
                    ->etc())
            );
    }

    public function test_halaman_profil_pengawas_menampilkan_label_jabatan_pengawas(): void
    {
        $this->seed();
        $pengawas1 = User::where('no_karyawan', 'WAS-000001')->firstOrFail();

        $this->actingAs($pengawas1)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->where('pengguna.label_jabatan_pengawas', 'Pengawas 1 (Ketua Pengawas)')
                ->has('ringkasan.plafon_pinjaman')
            );
    }

    public function test_ubah_pin_mandiri_memvalidasi_pin_lama_dan_memperbarui_pin_baru(): void
    {
        $user = User::factory()->create([
            'no_karyawan' => 'PIN-000001',
            'pin' => Hash::make('246810'),
        ]);

        // PIN lama salah -> ditolak 422
        $this->actingAs($user)
            ->postJson(route('security.pin.setup'), [
                'old_pin' => '000000',
                'pin' => '135791',
                'pin_confirmation' => '135791',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('old_pin');

        // PIN lama benar -> diperbarui
        $this->actingAs($user)
            ->postJson(route('security.pin.setup'), [
                'old_pin' => '246810',
                'pin' => '135791',
                'pin_confirmation' => '135791',
            ])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertTrue(Hash::check('135791', $user->fresh()->pin));
    }

    public function test_pengawas_memiliki_profil_anggota_aktif_dan_bisa_membuka_portal_anggota(): void
    {
        $this->seed();

        foreach (['WAS-000001', 'WAS-000002', 'WAS-000003'] as $noKaryawan) {
            $pengawas = User::where('no_karyawan', $noKaryawan)->firstOrFail();

            $this->actingAs($pengawas)
                ->get('/profile')
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('Profile/Edit')
                    ->where('pengguna.anggota.status', 'aktif'));

            $this->actingAs($pengawas)
                ->get(route('portal.dashboard'))
                ->assertOk();
        }

        // Simulasikan pengawas pada DB eksisting yang belum punya baris Anggota lalu jalankan migrasi backfill.
        $pengawasBaru = User::factory()->create([
            'no_karyawan' => 'WAS-990001',
            'name' => 'Pengawas Uji Migrasi',
            'jabatan_pengawas' => 2,
        ]);
        $pengawasBaru->assignRole('pengawas');
        $this->assertNull($pengawasBaru->fresh()->anggota);

        $migrasi = require database_path('migrations/2026_11_27_000005_buat_profil_anggota_pengawas_dan_sekretaris.php');
        $migrasi->up();

        $this->assertEquals('aktif', $pengawasBaru->fresh()->anggota?->status);

        $this->actingAs($pengawasBaru->fresh())
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pengguna.anggota.status', 'aktif'));

        $this->actingAs($pengawasBaru->fresh())
            ->get(route('portal.dashboard'))
            ->assertOk();
    }
}

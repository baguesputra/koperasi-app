<?php

namespace Tests\Concerns;

use App\Jobs\KirimWaJob;
use App\Models\Anggota;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

trait MembuatDataUji
{
    protected function masuk(string $noKaryawan): User
    {
        $user = User::where('no_karyawan', $noKaryawan)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    /**
     * Anggota aktif siap uji (dengan akun user & no_hp).
     *
     * @param  array  $override  kolom anggota tambahan
     */
    protected function buatAnggota(string $noKaryawan = 'TOP-900001', array $override = []): Anggota
    {
        $user = User::firstOrCreate(
            ['no_karyawan' => $noKaryawan],
            [
                'name' => 'Penguji '.substr($noKaryawan, -3),
                'email' => strtolower($noKaryawan).'@koperasi.test',
                'password' => Hash::make($noKaryawan),
                'harus_ganti_password' => false,
            ]
        );
        // ponytail: jamin role ada walau test lupa seed penuh
        Role::firstOrCreate(['name' => 'anggota', 'guard_name' => 'web']);
        $user->assignRole('anggota');

        return Anggota::create(array_merge([
            'user_id' => $user->id,
            'no_anggota' => 'ANG-UJI-'.substr($noKaryawan, -4),
            'nama' => 'Anggota Uji '.substr($noKaryawan, -3),
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Operasional',
            'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(3),
            'tanggal_jadi_anggota' => now()->subYears(2),
            'no_hp' => '08120000000'.random_int(1, 9),
            'status' => 'aktif',
        ], $override));
    }

    /**
     * Baca properti private KirimWaJob untuk assert payload.
     */
    protected function propertiWa(KirimWaJob $job): array
    {
        $baca = fn (string $prop) => Closure::bind(fn () => $this->{$prop}, $job, $job::class)();
        $dokumen = $baca('dokumen');

        return [
            'noHp' => $baca('noHp'),
            'event' => $baca('event'),
            'pesan' => $baca('pesan'),
            'dokumen' => $dokumen,
        ];
    }
}

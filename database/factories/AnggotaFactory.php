<?php

namespace Database\Factories;

use App\Models\Anggota;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnggotaFactory extends Factory
{
    protected $model = Anggota::class;

    public function definition(): array
    {
        return [
            'no_anggota' => Anggota::generateNoAnggota(),
            'no_karyawan' => fake()->unique()->numerify('TOP-######'),
            'no_ktp' => fake()->unique()->numerify('################'),
            'nama' => fake()->name(),
            'cabang' => fake()->randomElement(['Pusat', 'Cabang A', 'Cabang B', 'Cabang C']),
            'unit_bisnis' => fake()->word(),
            'department' => fake()->word(),
            'divisi' => fake()->word(),
            'jabatan' => fake()->jobTitle(),
            'tanggal_mulai_kerja' => fake()->dateTimeBetween('-5 years', 'now'),
            'tanggal_jadi_anggota' => fake()->dateTimeBetween('-3 years', 'now'),
            'status' => 'aktif',
            'no_hp' => fake()->phoneNumber(),
            'alamat' => fake()->address(),
        ];
    }
}

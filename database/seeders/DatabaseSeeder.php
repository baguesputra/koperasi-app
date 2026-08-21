<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            TabelTenorSeeder::class,
            SettingBungaSeeder::class,
            SettingLimitPinjamanSeeder::class,
            KasKoperasiSeeder::class,
            SettingSimpananSeeder::class,
            AnggotaSeeder::class,
            SimpananSeeder::class,
            PinjamanSeeder::class,
        ]);
    }
}

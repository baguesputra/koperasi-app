<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin: Akses penuh tanpa batas ke seluruh modul sistem
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Admin: Operasional administrasi kepegawaian, verifikasi awal, dan pengaturan
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'anggota.lihat', 'anggota.kelola', 'anggota.resign',
            'simpanan.lihat',
            'pinjaman.lihat', 'pinjaman.tinjau-admin',
            'kas.lihat',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.lihat',
            'pengaturan.kelola', 'user.kelola',
            'migrasi.kelola',
        ]);

        // 3. Bendahara: Evaluasi keuangan pinjaman tahap 2, kas, dan payroll
        $bendahara = Role::firstOrCreate(['name' => 'bendahara']);
        $bendahara->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat', 'simpanan.konfirmasi',
            'pinjaman.lihat', 'pinjaman.tinjau-bendahara',
            'limit.tinjau-bendahara',
            'angsuran.konfirmasi',
            'kas.lihat', 'kas.topup',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.lihat', 'shu.kelola',
            'portal.akses',
        ]);

        // 4. Ketua Koperasi: Persetujuan akhir pinjaman tahap 3 & aktivasi
        $ketua = Role::firstOrCreate(['name' => 'ketua_koperasi']);
        $ketua->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat',
            'pinjaman.lihat', 'pinjaman.approve-ketua',
            'limit.approve-ketua',
            'aktivasi.approve-ketua',
            'kas.lihat',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.lihat', 'shu.kelola',
            'portal.akses',
        ]);

        // 5. Pengawas: Murni view all dan penarikan laporan (auditor independen)
        $pengawas = Role::firstOrCreate(['name' => 'pengawas']);
        $pengawas->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat',
            'pinjaman.lihat',
            'kas.lihat',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.lihat',
            'portal.akses',
        ]);

        // 6. Anggota: Layanan mandiri portal anggota
        $anggota = Role::firstOrCreate(['name' => 'anggota']);
        $anggota->syncPermissions(['portal.akses']);
    }
}

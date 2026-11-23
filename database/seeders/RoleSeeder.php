<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin: Akses penuh tanpa batas ke seluruh modul & seluruh pengaturan sistem
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Sekretaris: Operasional administrasi kepegawaian, verifikasi awal, kelola user & organisasi
        $admin = Role::firstOrCreate(['name' => 'sekretaris']);
        $admin->syncPermissions([
            'anggota.lihat', 'anggota.kelola', 'anggota.resign',
            'simpanan.lihat',
            'tabungan.lihat',
            'pinjaman.lihat', 'pinjaman.tinjau-admin',
            'penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.ajukan',
            'kas.lihat',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.lihat',
            'user.kelola',
            'pengaturan.organisasi',
            'pengaturan.audit.lihat',
            'pengaturan.kebijakan.lihat',
            'migrasi.kelola',
        ]);

        // 3. Bendahara: Evaluasi keuangan pinjaman tahap 2, kas, payroll, dan pemantauan kebijakan
        $bendahara = Role::firstOrCreate(['name' => 'bendahara']);
        $bendahara->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat', 'simpanan.konfirmasi',
            'tabungan.lihat', 'tabungan.kelola',
            'pinjaman.lihat', 'pinjaman.tinjau-bendahara',
            'penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.ajukan', 'penghapusan-pinjaman.approve-bendahara',
            'limit.tinjau-bendahara',
            'angsuran.konfirmasi',
            'kas.lihat', 'kas.topup',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.lihat', 'shu.kelola',
            'pengaturan.kebijakan.lihat',
            'portal.akses',
        ]);

        // 4. Ketua Koperasi: Otorisasi pinjaman tahap 3 & penentu kebijakan bunga/limit/tenor/simpanan
        $ketua = Role::firstOrCreate(['name' => 'ketua_koperasi']);
        $ketua->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat',
            'tabungan.lihat',
            'pinjaman.lihat', 'pinjaman.approve-ketua',
            'penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.approve-ketua',
            'limit.approve-ketua',
            'aktivasi.approve-ketua',
            'kas.lihat',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.lihat', 'shu.kelola',
            'pengaturan.kebijakan.lihat',
            'pengaturan.kebijakan.kelola',
            'pengaturan.audit.lihat',
            'portal.akses',
        ]);

        // 5. Pengawas: View all data, view kebijakan, audit log, penarikan laporan, & approval berjenjang penghapusan pinjaman
        $pengawas = Role::firstOrCreate(['name' => 'pengawas']);
        $pengawas->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat',
            'tabungan.lihat',
            'pinjaman.lihat',
            'penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.approve-pengawas',
            'kas.lihat',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.lihat',
            'pengaturan.kebijakan.lihat',
            'pengaturan.audit.lihat',
            'portal.akses',
        ]);

        // 6. Anggota: Layanan mandiri portal anggota
        $anggota = Role::firstOrCreate(['name' => 'anggota']);
        $anggota->syncPermissions(['portal.akses']);
    }
}

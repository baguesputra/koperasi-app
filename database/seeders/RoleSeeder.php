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
            'tabungan.lihat', 'tabungan.tinjau-admin',
            'pinjaman.lihat', 'pinjaman.tinjau-admin',
            'penghapusan-pinjaman.lihat',
            'kas.lihat',
            'jurnal.lihat',
            'laporan.lihat',
            'shu.lihat',
            'user.kelola',
            'pengaturan.organisasi',
            'pengaturan.audit.lihat',
            'pengaturan.kebijakan.lihat',
            'migrasi.kelola',
            'portal.akses',
        ]);

        // 3. Bendahara: Evaluasi keuangan pinjaman tahap 2, kas, payroll, dan pemantauan kebijakan
        $bendahara = Role::firstOrCreate(['name' => 'bendahara']);
        $bendahara->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat', 'simpanan.konfirmasi',
            'tabungan.lihat', 'tabungan.kelola', 'tabungan.tinjau-bendahara',
            'pinjaman.lihat', 'pinjaman.tinjau-bendahara', 'pinjaman.cairkan',
            'penghapusan-pinjaman.lihat',
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
            'tabungan.lihat', 'tabungan.approve-ketua',
            'pinjaman.lihat', 'pinjaman.approve-ketua',
            'penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.ajukan',
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

        // 5. Pengawas: View-only data anggota, simpanan, tabungan, pinjaman, kas koperasi;
        //    penarikan & export laporan; portal anggota; serta approval penghapusan pinjaman.
        $pengawas = Role::firstOrCreate(['name' => 'pengawas']);
        $pengawas->syncPermissions([
            'anggota.lihat',
            'simpanan.lihat',
            'tabungan.lihat',
            'pinjaman.lihat',
            'penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.approve-pengawas',
            'kas.lihat',
            'laporan.lihat',
            'portal.akses',
        ]);

        // 6. Anggota: Layanan mandiri portal anggota
        $anggota = Role::firstOrCreate(['name' => 'anggota']);
        $anggota->syncPermissions(['portal.akses']);
    }
}

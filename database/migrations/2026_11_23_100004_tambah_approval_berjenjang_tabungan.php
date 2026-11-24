<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekening_tabungan', function (Blueprint $table) {
            if (! Schema::hasColumn('rekening_tabungan', 'admin_oleh')) {
                $table->foreignId('admin_oleh')->nullable()->after('diproses_oleh')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('rekening_tabungan', 'disetujui_admin_pada')) {
                $table->timestamp('disetujui_admin_pada')->nullable()->after('admin_oleh');
            }
            if (! Schema::hasColumn('rekening_tabungan', 'catatan_admin')) {
                $table->text('catatan_admin')->nullable()->after('disetujui_admin_pada');
            }
            if (! Schema::hasColumn('rekening_tabungan', 'bendahara_oleh')) {
                $table->foreignId('bendahara_oleh')->nullable()->after('catatan_admin')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('rekening_tabungan', 'disetujui_bendahara_pada')) {
                $table->timestamp('disetujui_bendahara_pada')->nullable()->after('bendahara_oleh');
            }
            if (! Schema::hasColumn('rekening_tabungan', 'catatan_bendahara')) {
                $table->text('catatan_bendahara')->nullable()->after('disetujui_bendahara_pada');
            }
            if (! Schema::hasColumn('rekening_tabungan', 'ketua_oleh')) {
                $table->foreignId('ketua_oleh')->nullable()->after('catatan_bendahara')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('rekening_tabungan', 'disetujui_ketua_pada')) {
                $table->timestamp('disetujui_ketua_pada')->nullable()->after('ketua_oleh');
            }
            if (! Schema::hasColumn('rekening_tabungan', 'catatan_ketua')) {
                $table->text('catatan_ketua')->nullable()->after('disetujui_ketua_pada');
            }
        });

        Schema::table('pengajuan_transaksi_tabungan', function (Blueprint $table) {
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'admin_oleh')) {
                $table->foreignId('admin_oleh')->nullable()->after('diproses_oleh')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'disetujui_admin_pada')) {
                $table->timestamp('disetujui_admin_pada')->nullable()->after('admin_oleh');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'catatan_admin')) {
                $table->text('catatan_admin')->nullable()->after('disetujui_admin_pada');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'bendahara_oleh')) {
                $table->foreignId('bendahara_oleh')->nullable()->after('catatan_admin')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'disetujui_bendahara_pada')) {
                $table->timestamp('disetujui_bendahara_pada')->nullable()->after('bendahara_oleh');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'catatan_bendahara')) {
                $table->text('catatan_bendahara')->nullable()->after('disetujui_bendahara_pada');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'ketua_oleh')) {
                $table->foreignId('ketua_oleh')->nullable()->after('catatan_bendahara')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'disetujui_ketua_pada')) {
                $table->timestamp('disetujui_ketua_pada')->nullable()->after('ketua_oleh');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'catatan_ketua')) {
                $table->text('catatan_ketua')->nullable()->after('disetujui_ketua_pada');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'dicairkan_oleh')) {
                $table->foreignId('dicairkan_oleh')->nullable()->after('catatan_ketua')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'tanggal_pencairan')) {
                $table->date('tanggal_pencairan')->nullable()->after('dicairkan_oleh');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'catatan_pencairan')) {
                $table->text('catatan_pencairan')->nullable()->after('tanggal_pencairan');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'bukti_pencairan_path')) {
                $table->string('bukti_pencairan_path')->nullable()->after('catatan_pencairan');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'bukti_pencairan_nama_asli')) {
                $table->string('bukti_pencairan_nama_asli')->nullable()->after('bukti_pencairan_path');
            }
            if (! Schema::hasColumn('pengajuan_transaksi_tabungan', 'bukti_pencairan_mime')) {
                $table->string('bukti_pencairan_mime', 100)->nullable()->after('bukti_pencairan_nama_asli');
            }
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rekening_tabungan MODIFY COLUMN status ENUM('diajukan','approved_admin','approved_bendahara','aktif','ditolak','ditutup') NOT NULL DEFAULT 'diajukan'");
            DB::statement("ALTER TABLE pengajuan_transaksi_tabungan MODIFY COLUMN status ENUM('menunggu','approved_admin','approved_bendahara','approved_ketua','disetujui','ditolak','dibatalkan') NOT NULL DEFAULT 'menunggu'");
        } else {
            Schema::table('rekening_tabungan', function (Blueprint $table) {
                $table->string('status', 30)->default('diajukan')->change();
            });
            Schema::table('pengajuan_transaksi_tabungan', function (Blueprint $table) {
                $table->string('status', 30)->default('menunggu')->change();
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $peta = [
            'tabungan.tinjau-admin' => ['super_admin', 'sekretaris'],
            'tabungan.tinjau-bendahara' => ['super_admin', 'bendahara'],
            'tabungan.approve-ketua' => ['super_admin', 'ketua_koperasi'],
        ];

        foreach ($peta as $namaIzin => $roles) {
            $perm = Permission::firstOrCreate(['name' => $namaIzin]);
            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo($perm);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::whereIn('name', [
            'tabungan.tinjau-admin',
            'tabungan.tinjau-bendahara',
            'tabungan.approve-ketua',
        ])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('pengajuan_transaksi_tabungan', function (Blueprint $table) {
            foreach (['admin_oleh', 'bendahara_oleh', 'ketua_oleh', 'dicairkan_oleh'] as $fk) {
                if (Schema::hasColumn('pengajuan_transaksi_tabungan', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
            foreach ([
                'disetujui_admin_pada',
                'catatan_admin',
                'disetujui_bendahara_pada',
                'catatan_bendahara',
                'disetujui_ketua_pada',
                'catatan_ketua',
                'tanggal_pencairan',
                'catatan_pencairan',
                'bukti_pencairan_path',
                'bukti_pencairan_nama_asli',
                'bukti_pencairan_mime',
            ] as $col) {
                if (Schema::hasColumn('pengajuan_transaksi_tabungan', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('rekening_tabungan', function (Blueprint $table) {
            foreach (['admin_oleh', 'bendahara_oleh', 'ketua_oleh'] as $fk) {
                if (Schema::hasColumn('rekening_tabungan', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
            foreach ([
                'disetujui_admin_pada',
                'catatan_admin',
                'disetujui_bendahara_pada',
                'catatan_bendahara',
                'disetujui_ketua_pada',
                'catatan_ketua',
            ] as $col) {
                if (Schema::hasColumn('rekening_tabungan', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

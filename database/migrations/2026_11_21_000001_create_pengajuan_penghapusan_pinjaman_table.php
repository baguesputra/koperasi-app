<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'jabatan_pengawas')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('jabatan_pengawas')->nullable()->after('status');
            });
        }

        Schema::create('pengajuan_penghapusan_pinjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pinjaman_id')->constrained('pinjaman')->cascadeOnDelete();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('nomor_dokumen')->nullable()->unique();
            $table->foreignId('pengaju_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_pengajuan');
            $table->text('alasan');

            $table->decimal('nominal_pinjaman', 15, 2);
            $table->decimal('total_pokok_terbayar', 15, 2)->default(0);
            $table->decimal('total_bunga_terbayar', 15, 2)->default(0);
            $table->unsignedInteger('sisa_cicilan')->default(0);
            $table->decimal('sisa_pokok', 15, 2);
            $table->decimal('sisa_bunga', 15, 2)->default(0);
            $table->decimal('sisa_total_tagihan', 15, 2);

            $table->string('status')->default('diajukan')->index();

            $table->foreignId('approved_bendahara_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_bendahara_at')->nullable();
            $table->text('catatan_bendahara')->nullable();

            $table->foreignId('approved_ketua_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_ketua_at')->nullable();
            $table->text('catatan_ketua')->nullable();

            $table->foreignId('approved_pengawas_3_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_pengawas_3_at')->nullable();
            $table->text('catatan_pengawas_3')->nullable();

            $table->foreignId('approved_pengawas_2_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_pengawas_2_at')->nullable();
            $table->text('catatan_pengawas_2')->nullable();

            $table->foreignId('approved_pengawas_1_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_pengawas_1_at')->nullable();
            $table->text('catatan_pengawas_1')->nullable();

            $table->foreignId('ditolak_oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditolak_pada')->nullable();
            $table->string('tahap_ditolak')->nullable();
            $table->text('catatan_tolak')->nullable();

            $table->timestamp('disetujui_pada')->nullable()->index();
            $table->timestamps();
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE pinjaman MODIFY COLUMN status ENUM('diajukan','ditinjau_bendahara','approved_admin','approved_bendahara','approved_ketua','aktif','lunas','ditolak','dihapusbukukan') NOT NULL DEFAULT 'diajukan'");
            DB::statement("ALTER TABLE angsuran MODIFY COLUMN status ENUM('belum_bayar','lunas','digantikan','dihapusbukukan') NOT NULL DEFAULT 'belum_bayar'");
            DB::statement("ALTER TABLE angsuran_percepatan MODIFY COLUMN status ENUM('belum_bayar','lunas','dihapusbukukan') NOT NULL DEFAULT 'belum_bayar'");
        } elseif ($driver === 'sqlite') {
            $this->rebuildAngsuranPercepatanSqlite();
        }

        $this->sinkronPermissionDanPengawas();
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_penghapusan_pinjaman');

        if (Schema::hasColumn('users', 'jabatan_pengawas')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('jabatan_pengawas');
            });
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::table('pinjaman')->where('status', 'dihapusbukukan')->update(['status' => 'aktif']);
            DB::table('angsuran')->where('status', 'dihapusbukukan')->update(['status' => 'belum_bayar']);
            DB::table('angsuran_percepatan')->where('status', 'dihapusbukukan')->update(['status' => 'belum_bayar']);

            DB::statement("ALTER TABLE pinjaman MODIFY COLUMN status ENUM('diajukan','ditinjau_bendahara','approved_admin','approved_bendahara','approved_ketua','aktif','lunas','ditolak') NOT NULL DEFAULT 'diajukan'");
            DB::statement("ALTER TABLE angsuran MODIFY COLUMN status ENUM('belum_bayar','lunas','digantikan') NOT NULL DEFAULT 'belum_bayar'");
            DB::statement("ALTER TABLE angsuran_percepatan MODIFY COLUMN status ENUM('belum_bayar','lunas') NOT NULL DEFAULT 'belum_bayar'");
        }
    }

    private function rebuildAngsuranPercepatanSqlite(): void
    {
        $info = DB::select("PRAGMA table_info('angsuran_percepatan')");
        if (empty($info)) {
            return;
        }

        $fields = array_map(fn ($c) => $c->name, $info);
        $daftar = implode(',', array_map(fn ($f) => '"'.$f.'"', $fields));

        Schema::disableForeignKeyConstraints();

        Schema::create('angsuran_percepatan_tmp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_percepatan_id')->constrained('pengajuan_percepatan')->cascadeOnDelete();
            $table->unsignedInteger('cicilan_ke');
            $table->decimal('nominal_pokok', 15, 2);
            $table->decimal('nominal_bunga', 15, 2);
            $table->decimal('total_bayar', 15, 2);
            $table->string('status')->default('belum_bayar');
            $table->date('tanggal_jatuh_tempo');
            $table->date('tanggal_konfirmasi_bayar')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        DB::statement("INSERT INTO angsuran_percepatan_tmp ({$daftar}) SELECT {$daftar} FROM angsuran_percepatan");
        Schema::drop('angsuran_percepatan');
        Schema::rename('angsuran_percepatan_tmp', 'angsuran_percepatan');
        Schema::enableForeignKeyConstraints();
    }

    private function sinkronPermissionDanPengawas(): void
    {
        $permissions = [
            'penghapusan-pinjaman.lihat',
            'penghapusan-pinjaman.ajukan',
            'penghapusan-pinjaman.approve-bendahara',
            'penghapusan-pinjaman.approve-ketua',
            'penghapusan-pinjaman.approve-pengawas',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $mapRole = [
            'super_admin' => $permissions,
            'sekretaris' => ['penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.ajukan'],
            'bendahara' => ['penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.ajukan', 'penghapusan-pinjaman.approve-bendahara'],
            'ketua_koperasi' => ['penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.approve-ketua'],
            'pengawas' => ['penghapusan-pinjaman.lihat', 'penghapusan-pinjaman.approve-pengawas'],
        ];

        foreach ($mapRole as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        if (Role::where('name', 'pengawas')->exists()) {
            $daftarPengawas = [
                ['no_karyawan' => 'WAS-000001', 'name' => 'Pengawas 1 (Ketua Pengawas)', 'email' => 'pengawas@koperasi.test', 'jabatan_pengawas' => 1],
                ['no_karyawan' => 'WAS-000002', 'name' => 'Pengawas 2 Koperasi', 'email' => 'pengawas2@koperasi.test', 'jabatan_pengawas' => 2],
                ['no_karyawan' => 'WAS-000003', 'name' => 'Pengawas 3 Koperasi', 'email' => 'pengawas3@koperasi.test', 'jabatan_pengawas' => 3],
            ];

            foreach ($daftarPengawas as $item) {
                $user = User::where('no_karyawan', $item['no_karyawan'])->first();
                if ($user) {
                    $user->update(['jabatan_pengawas' => $item['jabatan_pengawas']]);
                    if (! $user->hasRole('pengawas')) {
                        $user->assignRole('pengawas');
                    }
                } elseif (User::where('no_karyawan', 'WAS-000001')->exists()) {
                    $baru = User::create([
                        'no_karyawan' => $item['no_karyawan'],
                        'name' => $item['name'],
                        'email' => $item['email'],
                        'password' => Hash::make($item['no_karyawan']),
                        'status' => 'aktif',
                        'jabatan_pengawas' => $item['jabatan_pengawas'],
                    ]);
                    $baru->assignRole('pengawas');
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

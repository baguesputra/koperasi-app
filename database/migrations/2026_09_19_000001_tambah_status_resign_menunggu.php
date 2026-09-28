<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE anggota MODIFY COLUMN status ENUM('aktif','nonaktif','resign','resign_menunggu') NOT NULL DEFAULT 'aktif'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE anggota DROP CONSTRAINT IF EXISTS anggota_status_check');
            DB::statement("ALTER TABLE anggota ADD CONSTRAINT anggota_status_check CHECK (status IN ('aktif','nonaktif','resign','resign_menunggu'))");

            return;
        }

        // SQLite: enum = CHECK constraint. Rebuild via Schema builder (tanpa enum/check),
        // lalu salin isi. Gagal di titik mana pun = exception, tabel lama utuh.
        $info = DB::select("PRAGMA table_info('anggota')");

        if (empty($info)) {
            return;
        }

        $fields = array_map(fn ($c) => $c->name, $info);
        $daftar = implode(',', array_map(fn ($f) => '"'.$f.'"', $fields));

        Schema::disableForeignKeyConstraints();

        Schema::create('anggota_baru_menunggu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gate_id')->nullable();
            $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->nullOnDelete();
            $table->foreignId('departemen_id')->nullable()->constrained('departemen')->nullOnDelete();
            $table->foreignId('divisi_id')->nullable()->constrained('divisi')->nullOnDelete();
            $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $table->string('no_anggota')->unique();
            $table->string('no_karyawan')->nullable();
            $table->string('no_ktp')->nullable();
            $table->string('nama');
            $table->string('cabang');
            $table->string('unit_bisnis');
            $table->string('department')->nullable();
            $table->string('divisi')->nullable();
            $table->string('jabatan');
            $table->date('tanggal_mulai_kerja')->nullable();
            $table->date('tanggal_jadi_anggota');
            $table->string('status')->default('aktif');
            $table->date('tanggal_resign')->nullable();
            $table->text('alasan_resign')->nullable();
            $table->foreignId('resigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('resigned_settlement_json')->nullable();
            $table->json('reaktivasi_history_json')->nullable();
            $table->decimal('limit_custom', 15, 2)->nullable();
            $table->string('limit_custom_keterangan')->nullable();
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->string('foto_url')->nullable();
            $table->timestamps();
        });

        DB::statement("INSERT INTO anggota_baru_menunggu ({$daftar}) SELECT {$daftar} FROM anggota");
        Schema::drop('anggota');
        Schema::rename('anggota_baru_menunggu', 'anggota');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE anggota MODIFY COLUMN status ENUM('aktif','nonaktif','resign') NOT NULL DEFAULT 'aktif'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE anggota DROP CONSTRAINT IF EXISTS anggota_status_check');
            DB::statement("ALTER TABLE anggota ADD CONSTRAINT anggota_status_check CHECK (status IN ('aktif','nonaktif','resign'))");
        }
    }
};

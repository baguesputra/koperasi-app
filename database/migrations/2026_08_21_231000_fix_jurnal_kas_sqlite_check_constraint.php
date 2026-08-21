<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            return;
        }

        // Recreate jurnal_kas table with updated CHECK constraint for kategori
        DB::statement('
            CREATE TABLE jurnal_kas_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tipe TEXT NOT NULL CHECK (tipe IN (\'masuk\', \'keluar\')),
                kategori TEXT NOT NULL CHECK (kategori IN (
                    \'topup_bulanan\',\'pencairan_pinjaman\',\'pembayaran_angsuran\',
                    \'dana_sosial_bulanan\',\'pengeluaran_koperasi\',\'pengeluaran_dana_sosial\',\'saldo_awal\',
                    \'pelunasan_resign_pinjaman\',\'pelunasan_resign_simpanan\',\'simpanan_resign_masuk\',
                    \'return_simpanan_pokok\',\'return_simpanan_wajib\',
                    \'transfer_ke_dana_pinjaman\',\'terima_dari_pengembalian_simpanan\'
                )),
                kantong TEXT NOT NULL CHECK (kantong IN (\'pinjaman\', \'dana_sosial\', \'pengembalian_simpanan\')),
                jumlah DECIMAL(15,2) NOT NULL,
                saldo_setelah DECIMAL(15,2) NOT NULL DEFAULT 0,
                keterangan TEXT,
                sub_judul TEXT,
                referensi_id INTEGER,
                tanggal DATE NOT NULL,
                created_by INTEGER NOT NULL,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (created_by) REFERENCES users (id)
            )
        ');

        DB::statement('INSERT INTO jurnal_kas_new SELECT * FROM jurnal_kas');
        DB::statement('DROP TABLE jurnal_kas');
        DB::statement('ALTER TABLE jurnal_kas_new RENAME TO jurnal_kas');

        // Recreate indexes
        DB::statement('CREATE INDEX jurnal_kas_created_by_foreign ON jurnal_kas (created_by)');
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            return;
        }

        // Revert to original schema with limited kategori values
        DB::statement('
            CREATE TABLE jurnal_kas_old (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tipe TEXT NOT NULL CHECK (tipe IN (\'masuk\', \'keluar\')),
                kategori TEXT NOT NULL CHECK (kategori IN (
                    \'topup_bulanan\',\'pencairan_pinjaman\',\'pembayaran_angsuran\',
                    \'dana_sosial_bulanan\',\'pengeluaran_koperasi\',\'pengeluaran_dana_sosial\',\'saldo_awal\',
                    \'pelunasan_resign_pinjaman\',\'return_simpanan_pokok\',\'return_simpanan_wajib\',
                    \'transfer_ke_dana_pinjaman\',\'terima_dari_pengembalian_simpanan\'
                )),
                kantong TEXT NOT NULL CHECK (kantong IN (\'pinjaman\', \'dana_sosial\')),
                jumlah DECIMAL(15,2) NOT NULL,
                saldo_setelah DECIMAL(15,2) NOT NULL DEFAULT 0,
                keterangan TEXT,
                sub_judul TEXT,
                referensi_id INTEGER,
                tanggal DATE NOT NULL,
                created_by INTEGER NOT NULL,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (created_by) REFERENCES users (id)
            )
        ');

        DB::statement('INSERT INTO jurnal_kas_old SELECT * FROM jurnal_kas');
        DB::statement('DROP TABLE jurnal_kas');
        DB::statement('ALTER TABLE jurnal_kas_old RENAME TO jurnal_kas');

        DB::statement('CREATE INDEX jurnal_kas_created_by_foreign ON jurnal_kas (created_by)');
    }
};

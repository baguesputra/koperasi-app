<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KANTONG_DASAR = [
        'pinjaman',
        'dana_sosial',
        'pengembalian_simpanan',
        'simpanan',
        'bank',
        'kas_kecil',
    ];

    private const KANTONG_TAMBAHAN = [
        'tabungan',
    ];

    private const KATEGORI_DASAR = [
        'topup_bulanan',
        'pencairan_pinjaman',
        'pembayaran_angsuran',
        'dana_sosial_bulanan',
        'pengeluaran_koperasi',
        'pengeluaran_dana_sosial',
        'saldo_awal',
        'pelunasan_resign_pinjaman',
        'pelunasan_resign_simpanan',
        'simpanan_resign_masuk',
        'return_simpanan_pokok',
        'return_simpanan_wajib',
        'simpanan_pokok_masuk',
        'simpanan_wajib_masuk',
        'transfer_ke_dana_pinjaman',
        'terima_dari_pengembalian_simpanan',
        'talangan_sosial_ke_pinjaman',
        'talangan_simpanan_ke_pinjaman',
        'terima_talangan_dari_sosial',
        'terima_talangan_dari_simpanan',
        'kembali_talangan_dari_pinjaman',
        'kembali_talangan_ke_simpanan',
        'kembali_talangan_ke_sosial',
        'sisih_kas_kecil',
        'terima_sisih_kas_kecil',
        'pembayaran_shu',
    ];

    private const KATEGORI_TAMBAHAN = [
        'setor_tabungan',
        'tarik_tabungan',
        'return_tabungan_resign',
    ];

    public function up(): void
    {
        $this->alterEnum('kantong', array_merge(self::KANTONG_DASAR, self::KANTONG_TAMBAHAN));
        $this->alterEnum('kategori', array_merge(self::KATEGORI_DASAR, self::KATEGORI_TAMBAHAN));
    }

    public function down(): void
    {
        $this->alterEnum('kategori', self::KATEGORI_DASAR);
        $this->alterEnum('kantong', self::KANTONG_DASAR);
    }

    /**
     * @param  array<int, string>  $values
     */
    private function alterEnum(string $column, array $values): void
    {
        $driver = DB::connection()->getDriverName();
        $daftar = "'".implode("','", $values)."'";

        if ($driver === 'mysql') {
            $enum = implode(",\n                ", array_map(fn ($k) => "'{$k}'", $values));
            DB::statement("ALTER TABLE jurnal_kas MODIFY COLUMN {$column} ENUM(\n                {$enum}\n            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            $constraint = "jurnal_kas_{$column}_check";
            DB::statement("ALTER TABLE jurnal_kas DROP CONSTRAINT IF EXISTS {$constraint}");
            DB::statement("ALTER TABLE jurnal_kas ADD CONSTRAINT {$constraint} CHECK ({$column} IN ({$daftar}))");
        }
    }
};

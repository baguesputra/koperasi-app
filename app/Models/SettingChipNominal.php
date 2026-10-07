<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingChipNominal extends Model
{
    use HasFactory;

    protected $table = 'setting_chip_nominal';

    protected $fillable = ['grup', 'nominal', 'urutan'];

    protected $casts = ['nominal' => 'decimal:2', 'urutan' => 'integer'];

    public const GRUP_LABEL = [
        'santunan' => 'Santunan Dana Sosial',
        'pengeluaran' => 'Pengeluaran',
        'topup' => 'Topup Bank',
        'sisih' => 'Sisih Kas Kecil',
        'pinjaman_usulan' => 'Usulan Pinjaman (Bendahara/Ketua)',
        'limit_baru' => 'Limit Baru (Bendahara/Ketua)',
    ];

    public const DEFAULT = [
        'santunan' => [100_000, 300_000, 500_000],
        'pengeluaran' => [50_000, 100_000, 250_000, 500_000, 1_000_000],
        'topup' => [500_000, 1_000_000, 2_500_000, 5_000_000, 10_000_000],
        'sisih' => [500_000, 1_000_000, 2_500_000, 5_000_000, 10_000_000],
        'pinjaman_usulan' => [1_000_000, 3_000_000, 5_000_000, 7_000_000, 9_000_000],
        'limit_baru' => [1_000_000, 3_000_000, 5_000_000, 7_000_000, 9_000_000],
    ];

    public const MAKS_PER_GRUP = 8;

    public static function dikelompokkan(): array
    {
        return Cache::remember('chip_nominal_semua', 3600, function () {
            $hasil = [];
            foreach (array_keys(self::GRUP_LABEL) as $grup) {
                $hasil[$grup] = self::untuk($grup);
            }

            return $hasil;
        });
    }

    public static function untuk(string $grup): array
    {
        if (! Schema::hasTable('setting_chip_nominal')) {
            return array_map('floatval', self::DEFAULT[$grup] ?? []);
        }

        $daftar = static::where('grup', $grup)->orderBy('urutan')->orderBy('nominal')->pluck('nominal');

        if ($daftar->isEmpty()) {
            return array_map('floatval', self::DEFAULT[$grup] ?? []);
        }

        return $daftar->map(fn ($n) => (float) $n)->all();
    }
}

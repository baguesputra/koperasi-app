<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingKas extends Model
{
    use HasFactory;

    public const PAGU = 'pagu_pinjaman_bulanan';

    public const CADANGAN = 'cadangan_sosial_bulan';

    protected $table = 'setting_kas';

    protected $fillable = ['kunci', 'label', 'nominal'];

    protected $casts = ['nominal' => 'decimal:2'];

    public static function nilai(string $kunci, float $default): float
    {
        $nilai = static::where('kunci', $kunci)->value('nominal');

        return $nilai !== null ? (float) $nilai : $default;
    }
}

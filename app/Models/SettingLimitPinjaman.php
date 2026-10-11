<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingLimitPinjaman extends Model
{
    use HasFactory;

    protected $table = 'setting_limit_pinjaman';

    protected $fillable = [
        'kategori',
        'label',
        'min_bulan',
        'maks_bulan',
        'limit_maksimal',
    ];

    protected $casts = [
        'min_bulan' => 'integer',
        'maks_bulan' => 'integer',
        'limit_maksimal' => 'decimal:2',
    ];
}

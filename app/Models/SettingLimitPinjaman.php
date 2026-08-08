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
        'limit_maksimal',
    ];

    protected $casts = [
        'limit_maksimal' => 'decimal:2',
    ];
}
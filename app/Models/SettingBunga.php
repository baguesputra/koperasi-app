<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingBunga extends Model
{
    use HasFactory;

    protected $table = 'setting_bunga';

    protected $fillable = [
        'persentase',
        'berlaku_dari_tanggal',
    ];

    protected $casts = [
        'persentase' => 'decimal:2',
        'berlaku_dari_tanggal' => 'date',
    ];
}

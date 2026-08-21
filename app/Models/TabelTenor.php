<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabelTenor extends Model
{
    use HasFactory;

    protected $table = 'tabel_tenor';

    protected $fillable = [
        'nominal_min',
        'nominal_max',
        'tenor_maksimal_bulan',
    ];

    protected $casts = [
        'nominal_min' => 'decimal:2',
        'nominal_max' => 'decimal:2',
    ];
}

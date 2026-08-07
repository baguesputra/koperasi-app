<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KasKoperasi extends Model
{
    use HasFactory;

    protected $table = 'kas_koperasi';

    protected $fillable = [
        'saldo_saat_ini',
    ];

    protected $casts = [
        'saldo_saat_ini' => 'decimal:2',
    ];

    public function jurnal(): HasMany
    {
        return $this->hasMany(JurnalKas::class);
    }
}
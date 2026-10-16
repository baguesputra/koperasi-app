<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShuKomponen extends Model
{
    use HasFactory;

    protected $table = 'shu_komponen';

    protected $fillable = [
        'shu_periode_id',
        'slug',
        'label',
        'persen',
    ];

    protected $casts = [
        'persen' => 'decimal:4',
    ];

    public function periode(): BelongsTo
    {
        return $this->belongsTo(ShuPeriode::class, 'shu_periode_id');
    }
}

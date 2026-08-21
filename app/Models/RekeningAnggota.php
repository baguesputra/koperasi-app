<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekeningAnggota extends Model
{
    use HasFactory;

    protected $table = 'rekening_anggota';

    protected $fillable = ['anggota_id', 'nama_bank', 'no_rekening', 'atas_nama', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }
}

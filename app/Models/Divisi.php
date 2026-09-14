<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Divisi extends Model
{
    use HasFactory;

    protected $table = 'divisi';

    protected $fillable = ['gate_id', 'perusahaan_id', 'departemen_id', 'kode', 'nama', 'leadership_title', 'users_count', 'positions_count'];

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class);
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(Anggota::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    use HasFactory;

    protected $table = 'jabatan';

    protected $fillable = ['gate_id', 'perusahaan_id', 'departemen_id', 'division_id', 'nama', 'level', 'level_label'];

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class);
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'division_id');
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(Anggota::class);
    }
}

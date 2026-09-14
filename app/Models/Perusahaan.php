<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perusahaan extends Model
{
    use HasFactory;

    protected $table = 'perusahaan';

    protected $fillable = ['gate_id', 'kode', 'nama', 'direktur_gate_id', 'gm_gate_id'];

    public function departemen(): HasMany
    {
        return $this->hasMany(Departemen::class);
    }

    public function divisi(): HasMany
    {
        return $this->hasMany(Divisi::class);
    }

    public function jabatan(): HasMany
    {
        return $this->hasMany(Jabatan::class);
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(Anggota::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanAktivasi extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_aktivasi';

    protected $fillable = [
        'anggota_id', 'status', 'versi_syarat',
        'data_benar', 'setuju_syarat', 'catatan_ketua', 'tanggal_pengajuan',
    ];

    protected $casts = [
        'data_benar' => 'boolean',
        'setuju_syarat' => 'boolean',
        'tanggal_pengajuan' => 'date',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }
}

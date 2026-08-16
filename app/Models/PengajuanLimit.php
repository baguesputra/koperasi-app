<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanLimit extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_limit';

    protected $fillable = [
        'anggota_id', 'limit_saat_ini', 'limit_diminta', 'keterangan',
        'status', 'catatan_ketua', 'tanggal_pengajuan',
    ];

    protected $casts = [
        'limit_saat_ini' => 'decimal:2',
        'limit_diminta' => 'decimal:2',
        'tanggal_pengajuan' => 'date',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }
}
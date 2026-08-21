<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AngsuranPercepatan extends Model
{
    use HasFactory;

    protected $table = 'angsuran_percepatan';

    protected $fillable = [
        'pengajuan_percepatan_id', 'cicilan_ke', 'nominal_pokok', 'nominal_bunga',
        'total_bayar', 'status', 'tanggal_jatuh_tempo', 'tanggal_konfirmasi_bayar', 'confirmed_by',
    ];

    protected $casts = [
        'nominal_pokok' => 'decimal:2',
        'nominal_bunga' => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'tanggal_jatuh_tempo' => 'date',
        'tanggal_konfirmasi_bayar' => 'date',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPercepatan::class, 'pengajuan_percepatan_id');
    }
}

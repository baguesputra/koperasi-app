<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanPercepatan extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_percepatan';

    protected $fillable = [
        'pinjaman_id', 'tipe', 'tenor_lama', 'tenor_baru',
        'sisa_pokok_saat_approval', 'nominal_final', 'bulan_berlaku',
        'keterangan', 'status', 'catatan_bendahara', 'catatan_ketua', 'tanggal_pengajuan',
    ];

    protected $casts = [
        'sisa_pokok_saat_approval' => 'decimal:2',
        'nominal_final' => 'decimal:2',
        'tanggal_pengajuan' => 'date',
    ];

    public function pinjaman(): BelongsTo
    {
        return $this->belongsTo(Pinjaman::class);
    }

    public function angsuranBaru(): HasMany
    {
        return $this->hasMany(AngsuranPercepatan::class);
    }
}
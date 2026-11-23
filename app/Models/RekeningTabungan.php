<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekeningTabungan extends Model
{
    use HasFactory;

    protected $table = 'rekening_tabungan';

    protected $fillable = [
        'anggota_id',
        'no_rekening',
        'status',
        'saldo',
        'suku_bunga_saat_buka',
        'versi_syarat',
        'setuju_syarat',
        'tanggal_pengajuan',
        'tanggal_buka',
        'tanggal_tutup',
        'diproses_oleh',
        'catatan_pengurus',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'suku_bunga_saat_buka' => 'decimal:2',
        'setuju_syarat' => 'boolean',
        'tanggal_pengajuan' => 'date',
        'tanggal_buka' => 'date',
        'tanggal_tutup' => 'date',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(TabunganTransaksi::class);
    }

    public function pengajuanTransaksi(): HasMany
    {
        return $this->hasMany(PengajuanTransaksiTabungan::class);
    }

    public function bungaPeriode(): HasMany
    {
        return $this->hasMany(TabunganBungaPeriode::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShuKomponenPembayaran extends Model
{
    use HasFactory;

    protected $table = 'shu_komponen_pembayaran';

    protected $fillable = [
        'shu_periode_id',
        'kode_komponen',
        'nama_komponen',
        'nominal',
        'status_bayar',
        'tanggal_bayar',
        'dibayar_pada',
        'dibayar_oleh',
        'jurnal_id',
        'akun',
        'rincian',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'status_bayar' => 'string',
        'tanggal_bayar' => 'date',
        'dibayar_pada' => 'datetime',
        'rincian' => 'array',
    ];

    public function periode(): BelongsTo
    {
        return $this->belongsTo(ShuPeriode::class, 'shu_periode_id');
    }

    public function pembayar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_oleh');
    }

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(JurnalKas::class, 'jurnal_id');
    }

    public function sudahDibayar(): bool
    {
        return $this->status_bayar === 'sudah';
    }
}


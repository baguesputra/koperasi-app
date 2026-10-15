<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShuDetail extends Model
{
    use HasFactory;

    protected $table = 'shu_detail';

    protected $fillable = [
        'shu_periode_id',
        'anggota_id',
        'simp_pokok',
        'simp_wajib',
        'modal',
        'transaksi',
        'shu_modal',
        'shu_transaksi',
        'shu_bruto',
        'shu_bersih',
        'status_bayar',
        'dibayar_pada',
        'dibayar_oleh',
        'jurnal_id',
    ];

    protected $casts = [
        'simp_pokok' => 'decimal:2',
        'simp_wajib' => 'decimal:2',
        'modal' => 'decimal:2',
        'transaksi' => 'decimal:2',
        'shu_modal' => 'decimal:2',
        'shu_transaksi' => 'decimal:2',
        'shu_bruto' => 'decimal:2',
        'shu_bersih' => 'decimal:2',
        'dibayar_pada' => 'datetime',
    ];

    public function periode(): BelongsTo
    {
        return $this->belongsTo(ShuPeriode::class, 'shu_periode_id');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function dibayarOleh(): BelongsTo
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

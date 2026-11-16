<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalKas extends Model
{
    use HasFactory;

    protected $table = 'jurnal_kas';

    protected $fillable = [
        'tipe',
        'kategori',
        'sumber_topup',
        'kantong',
        'no_bukti',
        'jumlah',
        'saldo_setelah',
        'keterangan',
        'sub_judul',
        'referensi_id',
        'tanggal',
        'created_by',
        'dibatalkan_pada',
        'dibatalkan_oleh',
        'alasan_batal',
        'koreksi_dari_id',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal' => 'date',
        'dibatalkan_pada' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function koreksiDari(): BelongsTo
    {
        return $this->belongsTo(self::class, 'koreksi_dari_id');
    }

    public function pengeluaran(): BelongsTo
    {
        return $this->belongsTo(Pengeluaran::class, 'referensi_id');
    }
}

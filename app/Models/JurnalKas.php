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
        'kantong',
        'jumlah',
        'keterangan',
        'referensi_id',
        'tanggal',
        'created_by',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal' => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
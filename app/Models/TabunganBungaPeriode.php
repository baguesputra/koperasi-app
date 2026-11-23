<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabunganBungaPeriode extends Model
{
    use HasFactory;

    protected $table = 'tabungan_bunga_periode';

    protected $fillable = [
        'rekening_tabungan_id',
        'periode',
        'metode_hitung_snapshot',
        'suku_bunga_snapshot',
        'rincian_tarif_json',
        'saldo_dasar',
        'jumlah_hari',
        'nominal_bunga',
        'pajak_bunga',
        'bunga_bersih',
        'transaksi_id',
        'catatan',
        'diposting_oleh',
        'diposting_pada',
    ];

    protected $casts = [
        'suku_bunga_snapshot' => 'decimal:2',
        'rincian_tarif_json' => 'array',
        'saldo_dasar' => 'decimal:2',
        'jumlah_hari' => 'integer',
        'nominal_bunga' => 'decimal:2',
        'pajak_bunga' => 'decimal:2',
        'bunga_bersih' => 'decimal:2',
        'diposting_pada' => 'datetime',
    ];

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(RekeningTabungan::class, 'rekening_tabungan_id');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TabunganTransaksi::class, 'transaksi_id');
    }

    public function dipostingOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diposting_oleh');
    }
}

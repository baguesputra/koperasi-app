<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabunganTransaksi extends Model
{
    use HasFactory;

    protected $table = 'tabungan_transaksi';

    protected $fillable = [
        'rekening_tabungan_id',
        'anggota_id',
        'no_bukti',
        'jenis',
        'kanal',
        'nominal',
        'saldo_sebelum',
        'saldo_sesudah',
        'suku_bunga_snapshot',
        'tanggal',
        'keterangan',
        'bukti_path',
        'bukti_nama_asli',
        'bukti_mime',
        'jurnal_kas_id',
        'pengajuan_id',
        'koreksi_dari_id',
        'dibatalkan_pada',
        'dibatalkan_oleh',
        'alasan_batal',
        'created_by',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'saldo_sebelum' => 'decimal:2',
        'saldo_sesudah' => 'decimal:2',
        'suku_bunga_snapshot' => 'decimal:2',
        'tanggal' => 'date',
        'dibatalkan_pada' => 'datetime',
    ];

    public function isKredit(): bool
    {
        return in_array($this->jenis, ['setor', 'bunga', 'koreksi_masuk'], true);
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(RekeningTabungan::class, 'rekening_tabungan_id');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function jurnalKas(): BelongsTo
    {
        return $this->belongsTo(JurnalKas::class);
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanTransaksiTabungan::class, 'pengajuan_id');
    }

    public function koreksiDari(): BelongsTo
    {
        return $this->belongsTo(self::class, 'koreksi_dari_id');
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

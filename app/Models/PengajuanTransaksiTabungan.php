<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanTransaksiTabungan extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_transaksi_tabungan';

    protected $fillable = [
        'rekening_tabungan_id',
        'anggota_id',
        'jenis',
        'kanal',
        'nominal',
        'tanggal_transaksi',
        'bukti_path',
        'bukti_nama_asli',
        'bukti_mime',
        'rekening_tujuan_snapshot',
        'catatan_anggota',
        'status',
        'diproses_oleh',
        'diproses_pada',
        'catatan_pengurus',
        'bukti_pencairan_path',
        'bukti_pencairan_nama_asli',
        'bukti_pencairan_mime',
        'transaksi_id',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal_transaksi' => 'date',
        'diproses_pada' => 'datetime',
        'rekening_tujuan_snapshot' => 'array',
    ];

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(RekeningTabungan::class, 'rekening_tabungan_id');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TabunganTransaksi::class, 'transaksi_id');
    }
}

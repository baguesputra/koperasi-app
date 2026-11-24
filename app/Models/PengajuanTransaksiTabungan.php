<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanTransaksiTabungan extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_transaksi_tabungan';

    public const STATUS_MENUNGGU = [
        'menunggu',
        'approved_admin',
        'approved_bendahara',
        'approved_ketua',
    ];

    public const JENIS_PENARIKAN = [
        'tarik',
        'tutup_rekening',
    ];

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
        'admin_oleh',
        'disetujui_admin_pada',
        'catatan_admin',
        'bendahara_oleh',
        'disetujui_bendahara_pada',
        'catatan_bendahara',
        'ketua_oleh',
        'disetujui_ketua_pada',
        'catatan_ketua',
        'dicairkan_oleh',
        'tanggal_pencairan',
        'catatan_pencairan',
        'bukti_pencairan_path',
        'bukti_pencairan_nama_asli',
        'bukti_pencairan_mime',
        'transaksi_id',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal_transaksi' => 'date',
        'diproses_pada' => 'datetime',
        'disetujui_admin_pada' => 'datetime',
        'disetujui_bendahara_pada' => 'datetime',
        'disetujui_ketua_pada' => 'datetime',
        'tanggal_pencairan' => 'date',
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

    public function adminOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_oleh');
    }

    public function bendaharaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bendahara_oleh');
    }

    public function ketuaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ketua_oleh');
    }

    public function dicairkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicairkan_oleh');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TabunganTransaksi::class, 'transaksi_id');
    }
}

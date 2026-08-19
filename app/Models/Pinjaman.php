<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pinjaman extends Model
{
    use HasFactory;

    protected $table = 'pinjaman';

    protected $fillable = [
        'anggota_id',
        'pengaju_user_id',
        'nominal',
        'tenor_bulan',
        'keperluan',
        'snapshot_bank',
        'snapshot_no_rekening',
        'snapshot_atas_nama',
        'persentase_bunga',
        'status',
        'cair_oleh_bendahara',
        'sudah_pakai_privilege_reloan',
        'tanggal_pengajuan',
        'tanggal_pencairan',
        'catatan_bendahara',
        'catatan_ketua',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'persentase_bunga' => 'decimal:2',
        'cair_oleh_bendahara' => 'boolean',
        'sudah_pakai_privilege_reloan' => 'boolean',
        'tanggal_pengajuan' => 'date',
        'tanggal_pencairan' => 'date',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengaju_user_id');
    }

    public function angsuran(): HasMany
    {
        return $this->hasMany(Angsuran::class);
    }

    public function pengajuanPercepatan(): HasMany
    {
        return $this->hasMany(PengajuanPercepatan::class);
    }

    public function pengajuanPercepatanAktif()
    {
        return $this->hasOne(PengajuanPercepatan::class)->where('status', 'aktif');
    }

    public function angsuranBelumBayar(): HasMany
    {
        return $this->angsuran()->where('status', 'belum_bayar');
    }

    public function sisaAngsuran(): int
    {
        return $this->angsuranBelumBayar()->count();
    }
}

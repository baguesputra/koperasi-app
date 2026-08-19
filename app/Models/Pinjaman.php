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
        'sudah_pakai_percepatan',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'persentase_bunga' => 'decimal:2',
        'cair_oleh_bendahara' => 'boolean',
        'sudah_pakai_privilege_reloan' => 'boolean',
        'tanggal_pengajuan' => 'date',
        'tanggal_pencairan' => 'date',
        'sudah_pakai_percepatan' => 'boolean',
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

    public function angsuranBelumBayar(): HasMany
    {
        return $this->angsuran()->where('status', 'belum_bayar');
    }

    public function sisaAngsuran(): int
    {
        return $this->angsuranBelumBayar()->count();
    }

    public function pengajuanPercepatan()
    {
        return $this->hasMany(PengajuanPercepatan::class);
    }

    /**
     * Jadwal angsuran yang AKTIF sekarang - gabungan dari angsuran asli (yang belum digantikan)
     * dan angsuran_percepatan dari pengajuan yang sudah aktif (kalau ada).
     */
    public function jadwalAktif()
{
    $pengajuanAktif = $this->pengajuanPercepatan()->where('status', 'aktif')->latest()->first();
    $lama = $this->angsuran()->where('status', '!=', 'digantikan')->orderBy('cicilan_ke')->get();

    if (! $pengajuanAktif) {
        return $lama;
    }

    $baru = $pengajuanAktif->angsuranBaru()->orderBy('cicilan_ke')->get();

    return $lama->concat($baru);
    }

    public function totalCicilanAktif(): int
    {
        return $this->jadwalAktif()->count();
    }

    public function sisaCicilanAktif(): int
    {
        return $this->jadwalAktif()->where('status', 'belum_bayar')->count();
    }

    public function sisaTotalBayarAktif(): float
    {
        return (float) $this->jadwalAktif()->where('status', 'belum_bayar')->sum('total_bayar');
    }
}

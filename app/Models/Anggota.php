<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anggota extends Model
{
    use HasFactory;

    protected $table = 'anggota';

    protected $fillable = [
        'user_id',
        'no_anggota',
        'nama',
        'cabang',
        'unit_bisnis',
        'jabatan',
        'tanggal_mulai_kerja',
        'tanggal_jadi_anggota',
        'status',
    ];

    protected $casts = [
        'tanggal_mulai_kerja' => 'date',
        'tanggal_jadi_anggota' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function simpanan(): HasMany
    {
        return $this->hasMany(Simpanan::class);
    }

    public function pinjaman(): HasMany
    {
        return $this->hasMany(Pinjaman::class);
    }

    /**
     * Hitung lama keanggotaan dalam tahun (desimal), acuan untuk limit pinjaman.
     */
    public function getLamaKeanggotaanTahunAttribute(): float
    {
        return $this->tanggal_jadi_anggota->diffInDays(now()) / 365;
    }

    public function pinjamanAktif()
    {
        return $this->pinjaman()->whereIn('status', ['aktif'])->first();
    }

    public static function generateNoAnggota(): string
    {
        $tahun = now()->year;

        $nomorTerakhir = self::where('no_anggota', 'like', "ANG-{$tahun}-%")
            ->orderByDesc('no_anggota')
            ->value('no_anggota');

        $urutan = $nomorTerakhir
            ? ((int) substr($nomorTerakhir, -4)) + 1
            : 1;

        return sprintf('ANG-%d-%04d', $tahun, $urutan);
    }
}
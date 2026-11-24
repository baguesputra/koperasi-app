<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekeningTabungan extends Model
{
    use HasFactory;

    protected $table = 'rekening_tabungan';

    public const STATUS_MENUNGGU = [
        'diajukan',
        'approved_admin',
        'approved_bendahara',
    ];

    protected $fillable = [
        'anggota_id',
        'no_rekening',
        'status',
        'saldo',
        'suku_bunga_saat_buka',
        'versi_syarat',
        'setuju_syarat',
        'tanggal_pengajuan',
        'tanggal_buka',
        'tanggal_tutup',
        'diproses_oleh',
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
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'suku_bunga_saat_buka' => 'decimal:2',
        'setuju_syarat' => 'boolean',
        'tanggal_pengajuan' => 'date',
        'tanggal_buka' => 'date',
        'tanggal_tutup' => 'date',
        'disetujui_admin_pada' => 'datetime',
        'disetujui_bendahara_pada' => 'datetime',
        'disetujui_ketua_pada' => 'datetime',
    ];

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

    public function transaksi(): HasMany
    {
        return $this->hasMany(TabunganTransaksi::class);
    }

    public function pengajuanTransaksi(): HasMany
    {
        return $this->hasMany(PengajuanTransaksiTabungan::class);
    }

    public function bungaPeriode(): HasMany
    {
        return $this->hasMany(TabunganBungaPeriode::class);
    }

    public function tanggalBisaTarik(?int $masaTungguBulan = null): ?Carbon
    {
        if (! $this->tanggal_buka) {
            return null;
        }

        $bulan = $masaTungguBulan ?? (int) (SettingTabungan::current()->masa_tunggu_tarik_bulan ?? 12);

        return $this->tanggal_buka->copy()->startOfDay()->addMonthsNoOverflow(max(0, $bulan));
    }

    public function bisaTarik(?Carbon $pada = null, ?int $masaTungguBulan = null): bool
    {
        $bulan = $masaTungguBulan ?? (int) (SettingTabungan::current()->masa_tunggu_tarik_bulan ?? 12);
        if ($bulan <= 0) {
            return true;
        }

        $tglBukaKunci = $this->tanggalBisaTarik($bulan);
        if (! $tglBukaKunci) {
            return false;
        }

        $sekarang = ($pada ?? now())->copy()->startOfDay();

        return $sekarang->greaterThanOrEqualTo($tglBukaKunci);
    }

    public function sisaHariKunciTarik(?Carbon $pada = null, ?int $masaTungguBulan = null): int
    {
        $tglBukaKunci = $this->tanggalBisaTarik($masaTungguBulan);
        if (! $tglBukaKunci) {
            return 0;
        }

        $sekarang = ($pada ?? now())->copy()->startOfDay();
        if ($sekarang->greaterThanOrEqualTo($tglBukaKunci)) {
            return 0;
        }

        return (int) $sekarang->diffInDays($tglBukaKunci);
    }
}

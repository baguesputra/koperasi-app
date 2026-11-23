<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingBungaTabungan extends Model
{
    use HasFactory;

    protected $table = 'setting_bunga_tabungan';

    protected $fillable = [
        'persentase',
        'berlaku_dari_tanggal',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'persentase' => 'decimal:2',
        'berlaku_dari_tanggal' => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function tarifAktifPada(?CarbonInterface $tanggal = null): ?self
    {
        $tgl = ($tanggal ?? now())->toDateString();

        return static::whereDate('berlaku_dari_tanggal', '<=', $tgl)
            ->orderByDesc('berlaku_dari_tanggal')
            ->orderByDesc('id')
            ->first();
    }

    public static function persentaseAktif(?CarbonInterface $tanggal = null): float
    {
        $tarif = static::tarifAktifPada($tanggal);

        if ($tarif) {
            return (float) $tarif->persentase;
        }

        $terbaru = static::orderByDesc('berlaku_dari_tanggal')->orderByDesc('id')->first();

        return $terbaru ? (float) $terbaru->persentase : 2.00;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingTabungan extends Model
{
    use HasFactory;

    public const METODE_BUNGA = [
        'saldo_harian' => 'Saldo Harian (Dikreditkan Bulanan)',
        'saldo_terendah' => 'Saldo Terendah Bulanan',
        'rata_rata_harian' => 'Saldo Rata-Rata Harian Bulanan',
    ];

    protected $table = 'setting_tabungan';

    protected $fillable = [
        'metode_bunga',
        'min_setor',
        'saldo_minimal',
        'min_tarik',
        'masa_tunggu_tarik_bulan',
        'bank_tujuan_nama',
        'bank_tujuan_rekening',
        'bank_tujuan_atas_nama',
        'buka_rekening_aktif',
        'updated_by',
    ];

    protected $casts = [
        'min_setor' => 'decimal:2',
        'saldo_minimal' => 'decimal:2',
        'min_tarik' => 'decimal:2',
        'masa_tunggu_tarik_bulan' => 'integer',
        'buka_rekening_aktif' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        return static::first() ?? static::create([
            'metode_bunga' => 'saldo_harian',
            'min_setor' => 50_000,
            'saldo_minimal' => 50_000,
            'min_tarik' => 50_000,
            'masa_tunggu_tarik_bulan' => 12,
            'bank_tujuan_nama' => 'Bank Mandiri',
            'bank_tujuan_rekening' => '000-0000000-0',
            'bank_tujuan_atas_nama' => 'Koperasi Karyawan Karya Mandiri',
            'buka_rekening_aktif' => true,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengeluaran extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran';

    public const TIPE = ['pajak', 'santunan', 'lain_lain', 'perlengkapan', 'perijinan', 'bank'];

    public const TIPE_LABEL = [
        'pajak' => 'Pajak',
        'santunan' => 'Santunan',
        'lain_lain' => 'Lain-lain',
        'perlengkapan' => 'Perlengkapan',
        'perijinan' => 'Perijinan',
        'bank' => 'Bank',
    ];

    public const AKUN = ['bank', 'kas_kecil'];

    public const AKUN_LABEL = [
        'bank' => 'Bank',
        'kas_kecil' => 'Kas Kecil',
    ];

    protected $fillable = ['jenis', 'tipe', 'akun', 'jumlah', 'keterangan', 'tanggal', 'input_by'];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal' => 'date',
    ];

    public function inputOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }
}

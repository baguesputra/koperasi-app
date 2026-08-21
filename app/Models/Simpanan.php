<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Simpanan extends Model
{
    use HasFactory;

    protected $table = 'simpanan';

    protected $fillable = [
        'anggota_id',
        'jenis',
        'jumlah',
        'bulan_periode',
        'tanggal_input',
        'input_by',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal_input' => 'date',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function inputOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }
}

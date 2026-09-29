<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KlaimDanaSosial extends Model
{
    use HasFactory;

    protected $table = 'klaim_dana_sosial';

    public const JENIS = ['sakit', 'lahiran_khitan', 'duka', 'bahagia_menikah'];

    public const SUB_TIPE_SAKIT = ['rajal', 'opname'];

    public const HUBUNGAN_DUKA = ['orang_tua', 'suami', 'istri', 'anak'];

    protected $fillable = [
        'anggota_id', 'jenis', 'sub_tipe', 'hubungan', 'tanggal_kejadian', 'lama_hari',
        'keterangan', 'foto_path', 'nominal_bendahara', 'nominal_final', 'status',
        'catatan_bendahara', 'catatan_ketua', 'pengeluaran_id', 'tanggal_pengajuan',
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
        'tanggal_pengajuan' => 'date',
        'nominal_bendahara' => 'decimal:2',
        'nominal_final' => 'decimal:2',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function pengeluaran(): BelongsTo
    {
        return $this->belongsTo(Pengeluaran::class, 'pengeluaran_id');
    }

    public function berjalan(): bool
    {
        return in_array($this->status, ['diajukan', 'approved_bendahara'], true);
    }

    public function fotoUrl(): ?string
    {
        return $this->foto_path ? asset('storage/'.$this->foto_path) : null;
    }
}

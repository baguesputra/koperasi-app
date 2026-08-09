<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $fillable = ['user_id', 'aksi', 'keterangan', 'data_lama', 'data_baru'];

    protected $casts = [
        'data_lama' => 'array',
        'data_baru' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(string $aksi, string $keterangan, $dataLama = null, $dataBaru = null): void
    {
        self::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'data_lama' => $dataLama,
            'data_baru' => $dataBaru,
        ]);
    }
}
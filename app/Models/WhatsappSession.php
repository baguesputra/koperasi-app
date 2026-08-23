<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappSession extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_sessions';

    protected $fillable = [
        'session_id',
        'name',
        'description',
        'is_default',
        'is_active',
        'phone_number',
        'phone_name',
        'last_connected_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_connected_at' => 'datetime',
    ];

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    public static function getActive(): Collection
    {
        return static::where('is_active', true)->get();
    }

    public function logs()
    {
        return $this->hasMany(WhatsappLog::class, 'session_id', 'session_id');
    }
}

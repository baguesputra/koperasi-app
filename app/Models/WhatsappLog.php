<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'session_id',
        'to',
        'message',
        'status',
        'reference_type',
        'reference_id',
        'error',
    ];

    protected $casts = [
        'reference_id' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(WhatsappSession::class, 'session_id', 'session_id');
    }
}

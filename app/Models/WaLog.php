<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaLog extends Model
{
    protected $fillable = ['anggota_id', 'penerima', 'event', 'pesan', 'status', 'error'];
}

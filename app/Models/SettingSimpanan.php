<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingSimpanan extends Model
{
    use HasFactory;

    protected $table = 'setting_simpanan';

    protected $fillable = ['jenis', 'label', 'nominal'];

    protected $casts = ['nominal' => 'decimal:2'];
}

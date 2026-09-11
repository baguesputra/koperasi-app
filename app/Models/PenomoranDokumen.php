<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenomoranDokumen extends Model
{
    protected $table = 'penomoran_dokumen';

    protected $fillable = ['jenis', 'tahun', 'terakhir'];
}

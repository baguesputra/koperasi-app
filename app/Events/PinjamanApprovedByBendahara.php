<?php

namespace App\Events;

use App\Models\Pinjaman;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PinjamanApprovedByBendahara
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Pinjaman $pinjaman,
        public string $catatan
    ) {}
}

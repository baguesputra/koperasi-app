<?php

namespace App\Jobs;

use App\Models\Anggota;
use App\Services\Gate\GateClient;
use App\Services\Gate\SinkronisasiAnggotaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SinkronAnggotaJit implements ShouldQueue
{
    use Queueable;

    public $tries = 1;

    public $timeout = 20;

    public function __construct(public int $userId, public string $email) {}

    public function handle(GateClient $gate, SinkronisasiAnggotaService $sinkron): void
    {
        $anggota = Anggota::where('user_id', $this->userId)->first();
        if (! $anggota) {
            return;
        }
        if ($anggota->gate_synced_at && $anggota->gate_synced_at->gt(now()->subMinutes(15))) {
            return;
        }
        if (! $baris = $gate->cariKaryawanByEmail($this->email)) {
            return;
        }
        $sinkron->enrichDariGate($anggota->user, $baris);
        Log::info('Gate JIT OK', ['user_id' => $this->userId, 'email' => $this->email]);
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Gate JIT gagal', ['user_id' => $this->userId, 'error' => $e->getMessage()]);
    }
}

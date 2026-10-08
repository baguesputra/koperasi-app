<?php

namespace App\Jobs;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\GateSyncLog;
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

    public function handle(GateClient $gate, SinkronisasiAnggotaService $sinkron): string
    {
        $anggota = Anggota::where('user_id', $this->userId)->first();
        if (! $anggota) {
            $this->catat('dilewati', 'tanpa relasi anggota');

            return 'tanpa-anggota';
        }
        if ($anggota->gate_synced_at && $anggota->gate_synced_at->gt(now()->subMinutes(15))) {
            return 'segar';
        }
        try {
            $baris = $gate->cariKaryawanByEmail($this->email);
        } catch (\Throwable $e) {
            report($e);
            $this->catat('gagal', $e->getMessage());

            return 'gagal-gate';
        }
        if (! $baris) {
            $this->catat('dilewati', 'tak ketemu di Gate');

            return 'tak-ketemu';
        }
        try {
            $sinkron->enrichDariGate($anggota->user, $baris);
        } catch (\Throwable $e) {
            report($e);
            $this->catat('gagal', $e->getMessage());

            return 'gagal-enrich';
        }
        $this->catat('ok');
        Log::info('Gate JIT OK', ['user_id' => $this->userId, 'email' => $this->email]);

        return 'ok';
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Gate JIT gagal', ['user_id' => $this->userId, 'error' => $e->getMessage()]);
        try {
            $this->catat('gagal', $e->getMessage());
        } catch (\Throwable) {
        }
    }

    private function catat(string $hasil, ?string $error = null): void
    {
        GateSyncLog::create([
            'kind' => 'karyawan',
            'source' => 'login',
            'is_dry_run' => false,
            'count_baru' => 0,
            'count_diperbarui' => $hasil === 'ok' ? 1 : 0,
            'count_gagal' => $hasil === 'gagal' ? 1 : 0,
            'count_dilewati' => $hasil === 'dilewati' ? 1 : 0,
            'user_id' => $this->userId,
        ]);
        AuditLog::catat(
            $hasil === 'gagal' ? 'sinkron_gate_login_gagal' : 'sinkron_gate_login',
            "Sync login {$this->email}".($error ? ": {$error}" : ' OK.'),
            null,
            $error ? ['error' => $error] : null,
            $this->userId
        );
    }
}

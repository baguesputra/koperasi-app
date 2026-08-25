<?php

namespace App\Jobs;

use App\Models\WaLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class KirimWaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        private ?string $noHp,
        private ?int $anggotaId,
        private string $event,
        private string $pesan,
    ) {}

    public function handle(): void
    {
        $nomor = self::normalisasi($this->noHp);

        if (! $nomor) {
            WaLog::create([
                'anggota_id' => $this->anggotaId,
                'penerima' => $this->noHp ?? '-',
                'event' => $this->event,
                'pesan' => $this->pesan,
                'status' => 'dilewati',
                'error' => 'Nomor HP tidak tersedia/tidak valid',
            ]);

            return;
        }

        try {
            Http::baseUrl(config('services.wa.url'))
                ->withToken(config('services.wa.token'))
                ->timeout(config('services.wa.timeout'))
                ->post('/send', ['to' => $nomor, 'message' => $this->pesan])
                ->throw();

            WaLog::create([
                'anggota_id' => $this->anggotaId,
                'penerima' => $nomor,
                'event' => $this->event,
                'pesan' => $this->pesan,
                'status' => 'terkirim',
            ]);
        } catch (ConnectionException|RequestException $e) {
            $this->catatGagal($nomor, $e->getMessage());
        }
    }

    private function catatGagal(string $nomor, string $error): void
    {
        WaLog::create([
            'anggota_id' => $this->anggotaId,
            'penerima' => $nomor,
            'event' => $this->event,
            'pesan' => $this->pesan,
            'status' => 'gagal',
            'error' => substr($error, 0, 255),
        ]);
    }

    public static function normalisasi(?string $noHp): ?string
    {
        $digits = preg_replace('/\D/', '', $noHp ?? '');

        if ($digits === '' || strlen($digits) < 8) {
            return null;
        }

        return match (true) {
            str_starts_with($digits, '62') => $digits,
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => null,
        };
    }
}

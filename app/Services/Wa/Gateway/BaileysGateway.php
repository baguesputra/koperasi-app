<?php

namespace App\Services\Wa\Gateway;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class BaileysGateway implements WaGateway
{
    public function status(): ?array
    {
        try {
            $status = $this->client()->get('/status')->throw()->json() ?? [];

            return [
                'connected' => (bool) ($status['connected'] ?? false),
                'has_qr' => (bool) ($status['hasQR'] ?? false),
            ];
        } catch (ConnectionException|RequestException) {
            return null;
        }
    }

    public function qr(): ?string
    {
        try {
            $qr = $this->client()->get('/qr')->throw()->json('qr');

            return is_string($qr) ? $qr : null;
        } catch (ConnectionException|RequestException) {
            return null;
        }
    }

    public function sendText(string $nomor, string $pesan): void
    {
        $this->client()->post('/send', [
            'to' => $nomor,
            'message' => $pesan,
        ])->throw();
    }

    public function sendDocument(string $nomor, string $filename, string $base64, string $caption, string $mimetype): void
    {
        $this->client()->post('/send-document', [
            'to' => $nomor,
            'filename' => $filename,
            'caption' => $caption,
            'base64' => $base64,
        ])->throw();
    }

    public function logout(): void
    {
        $this->client()->post('/logout')->throw();
    }

    public function canManageSession(): bool
    {
        return true;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.wa.url'))
            ->withToken(config('services.wa.token'))
            ->timeout(config('services.wa.timeout'));
    }
}

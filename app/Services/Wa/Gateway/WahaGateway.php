<?php

namespace App\Services\Wa\Gateway;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WahaGateway implements WaGateway
{
    public function status(): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $response = $this->client()->get($this->url('/api/sessions/'.rawurlencode($this->session())));

            if ($response->status() === 404) {
                return ['connected' => false, 'has_qr' => false];
            }

            $response->throw();

            return [
                'connected' => $response->json('status') === 'WORKING',
                'has_qr' => false,
            ];
        } catch (ConnectionException|RequestException) {
            return null;
        }
    }

    public function qr(): ?string
    {
        return null;
    }

    public function sendText(string $nomor, string $pesan): void
    {
        $this->client()->post($this->url('/api/sendText'), [
            'session' => $this->session(),
            'chatId' => $this->chatId($nomor),
            'text' => $pesan,
        ])->throw();
    }

    public function sendDocument(string $nomor, string $filename, string $base64, string $caption, string $mimetype): void
    {
        $this->client()->post($this->url('/api/sendFile'), [
            'session' => $this->session(),
            'chatId' => $this->chatId($nomor),
            'caption' => $caption,
            'file' => [
                'mimetype' => $mimetype,
                'filename' => $filename,
                'data' => $base64,
            ],
        ])->throw();
    }

    public function logout(): void
    {
        throw new RuntimeException('Sesi WAHA dikelola oleh penyedia layanan.');
    }

    public function canManageSession(): bool
    {
        return false;
    }

    private function client(): PendingRequest
    {
        $this->ensureConfigured();

        return Http::acceptJson()
            ->withHeaders(['X-Api-Key' => (string) config('services.waha.api_key')])
            ->withOptions(['verify' => (bool) config('services.waha.verify_tls', true)])
            ->timeout((float) config('services.waha.timeout', 15));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.waha.url'), '/').'/'.ltrim($path, '/');
    }

    private function session(): string
    {
        return (string) config('services.waha.session', 'waha');
    }

    private function chatId(string $nomor): string
    {
        return $nomor.'@c.us';
    }

    private function configured(): bool
    {
        return filled(config('services.waha.url')) && filled(config('services.waha.api_key'));
    }

    private function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('WAHA_URL atau WAHA_API_KEY belum diatur.');
        }
    }
}

<?php

namespace App\Notifications\Channels;

use App\Models\WhatsappLog;
use App\Models\WhatsappSession;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaileysChannel
{
    public function send($notifiable, Notification $notification): void
    {
        $message = $notification->toBaileys($notifiable);

        if (! $message) {
            return;
        }

        $phone = $this->getPhoneNumber($notifiable);

        if (! $phone) {
            Log::warning('Baileys: No phone number for notifiable', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->getKey(),
            ]);

            return;
        }

        if (! $this->isValidPhone($phone)) {
            Log::warning('Baileys: Invalid phone number', [
                'phone' => $phone,
                'notifiable_type' => get_class($notifiable),
            ]);

            return;
        }

        $sessionId = $this->getSessionId($notification);
        $this->sendToBaileysService($phone, $message, $notification, $sessionId);
    }

    protected function getPhoneNumber($notifiable): ?string
    {
        if (method_exists($notifiable, 'getWhatsAppPhoneNumber')) {
            return $notifiable->getWhatsAppPhoneNumber();
        }

        if (isset($notifiable->anggota)) {
            return $notifiable->anggota->no_hp;
        }

        if (isset($notifiable->no_hp)) {
            return $notifiable->no_hp;
        }

        return null;
    }

    protected function isValidPhone(string $phone): bool
    {
        $cleaned = preg_replace('/\D/', '', $phone);

        if (preg_match('/^628\d{8,11}$/', $cleaned)) {
            return true;
        }

        if (preg_match('/^08\d{8,11}$/', $cleaned)) {
            return true;
        }

        return false;
    }

    protected function getSessionId(Notification $notification): string
    {
        if (method_exists($notification, 'getSessionId')) {
            return $notification->getSessionId();
        }

        $defaultSession = WhatsappSession::getDefault();

        return $defaultSession?->session_id ?? 'main';
    }

    protected function sendToBaileysService(string $phone, string $message, Notification $notification, string $sessionId): void
    {
        $url = config('services.baileys.url');
        $token = config('services.baileys.token');
        $timeout = config('services.baileys.timeout', 10);

        if (! $url || ! $token) {
            Log::error('Baileys: Missing configuration (url or token)');

            return;
        }

        try {
            $referenceType = null;
            $referenceId = null;

            if (method_exists($notification, 'getReference')) {
                $ref = $notification->getReference();
                if ($ref) {
                    $referenceType = get_class($ref);
                    $referenceId = $ref->getKey();
                }
            }

            WhatsappLog::create([
                'session_id' => $sessionId,
                'to' => $phone,
                'message' => $message,
                'status' => 'pending',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            $response = Http::timeout($timeout)
                ->withToken($token)
                ->post($url.'/api/send', [
                    'to' => $phone,
                    'message' => $message,
                    'sessionId' => $sessionId,
                ]);

            if ($response->successful()) {
                WhatsappLog::where('session_id', $sessionId)
                    ->where('to', $phone)
                    ->where('message', $message)
                    ->where('status', 'pending')
                    ->latest()
                    ->first()
                    ?->update(['status' => 'sent']);
            } else {
                WhatsappLog::where('session_id', $sessionId)
                    ->where('to', $phone)
                    ->where('message', $message)
                    ->where('status', 'pending')
                    ->latest()
                    ->first()
                    ?->update([
                        'status' => 'failed',
                        'error' => $response->body(),
                    ]);

                Log::error('Baileys: Failed to send message', [
                    'phone' => $phone,
                    'sessionId' => $sessionId,
                    'response' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            WhatsappLog::where('session_id', $sessionId)
                ->where('to', $phone)
                ->where('message', $message)
                ->where('status', 'pending')
                ->latest()
                ->first()
                ?->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);

            Log::error('Baileys: Exception sending message', [
                'phone' => $phone,
                'sessionId' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

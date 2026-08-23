<?php

namespace App\Notifications;

use App\Models\Pinjaman;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseLoanNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Pinjaman $pinjaman,
        public ?string $catatan = null
    ) {}

    public function via($notifiable): array
    {
        return ['baileys'];
    }

    public function getReference()
    {
        return $this->pinjaman;
    }

    public function getSessionId(): string
    {
        return 'main';
    }

    protected function formatNominal(float|int $nominal): string
    {
        return 'Rp '.number_format($nominal, 0, ',', '.');
    }

    protected function getDashboardUrl(): string
    {
        return config('app.url').'/pinjaman/'.$this->pinjaman->id;
    }
}

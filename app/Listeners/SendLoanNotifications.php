<?php

namespace App\Listeners;

use App\Events\PinjamanApprovedByBendahara;
use App\Events\PinjamanApprovedByKetua;
use App\Events\PinjamanCreated;
use App\Events\PinjamanDisbursed;
use App\Events\PinjamanRejected;
use App\Models\User;
use App\Notifications\LoanApplicationSubmitted;
use App\Notifications\LoanApprovedByChairman;
use App\Notifications\LoanApprovedByTreasurer;
use App\Notifications\LoanDisbursed;
use App\Notifications\LoanRejected;
use App\Notifications\NewLoanApplicationForTreasurer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendLoanNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function handle(object $event): void
    {
        match (get_class($event)) {
            PinjamanCreated::class => $this->handleCreated($event),
            PinjamanApprovedByBendahara::class => $this->handleApprovedByBendahara($event),
            PinjamanApprovedByKetua::class => $this->handleApprovedByKetua($event),
            PinjamanRejected::class => $this->handleRejected($event),
            PinjamanDisbursed::class => $this->handleDisbursed($event),
            default => null,
        };
    }

    protected function handleCreated(PinjamanCreated $event): void
    {
        $pinjaman = $event->pinjaman->load('anggota.user');
        $anggota = $pinjaman->anggota;

        if ($anggota?->user) {
            Notification::send($anggota->user, new LoanApplicationSubmitted($pinjaman));
        }

        $bendaharas = User::role('bendahara')
            ->whereHas('anggota', fn ($q) => $q->whereNotNull('no_hp'))
            ->with('anggota')
            ->get();

        foreach ($bendaharas as $bendahara) {
            if ($bendahara->anggota?->no_hp) {
                Notification::send($bendahara, new NewLoanApplicationForTreasurer($pinjaman));
            }
        }

        Log::info('Loan notifications sent for created pinjaman', [
            'pinjaman_id' => $pinjaman->id,
            'anggota_notified' => $anggota?->user?->id ? true : false,
            'bendahara_count' => $bendaharas->count(),
        ]);
    }

    protected function handleApprovedByBendahara(PinjamanApprovedByBendahara $event): void
    {
        $pinjaman = $event->pinjaman->load('anggota.user');
        $anggota = $pinjaman->anggota;

        if ($anggota?->user) {
            Notification::send($anggota->user, new LoanApprovedByTreasurer($pinjaman, $event->catatan));
        }

        Log::info('Loan approved by bendahara notification sent', [
            'pinjaman_id' => $pinjaman->id,
        ]);
    }

    protected function handleApprovedByKetua(PinjamanApprovedByKetua $event): void
    {
        $pinjaman = $event->pinjaman->load('anggota.user');
        $anggota = $pinjaman->anggota;

        if ($anggota?->user) {
            Notification::send($anggota->user, new LoanApprovedByChairman($pinjaman, $event->catatan));
        }

        Log::info('Loan approved by ketua notification sent', [
            'pinjaman_id' => $pinjaman->id,
        ]);
    }

    protected function handleRejected(PinjamanRejected $event): void
    {
        $pinjaman = $event->pinjaman->load('anggota.user');
        $anggota = $pinjaman->anggota;

        if ($anggota?->user) {
            Notification::send($anggota->user, new LoanRejected($pinjaman, $event->catatan));
        }

        Log::info('Loan rejected notification sent', [
            'pinjaman_id' => $pinjaman->id,
            'rejected_by' => $event->rejectedBy,
        ]);
    }

    protected function handleDisbursed(PinjamanDisbursed $event): void
    {
        $pinjaman = $event->pinjaman->load('anggota.user');
        $anggota = $pinjaman->anggota;

        if ($anggota?->user) {
            Notification::send($anggota->user, new LoanDisbursed($pinjaman, $event->catatan));
        }

        Log::info('Loan disbursed notification sent', [
            'pinjaman_id' => $pinjaman->id,
        ]);
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('Failed to send loan notification', [
            'event' => get_class($event),
            'pinjaman_id' => $event->pinjaman->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}

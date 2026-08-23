<?php

namespace App\Providers;

use App\Events\PinjamanApprovedByBendahara;
use App\Events\PinjamanApprovedByKetua;
use App\Events\PinjamanCreated;
use App\Events\PinjamanDisbursed;
use App\Events\PinjamanRejected;
use App\Listeners\SendLoanNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PinjamanCreated::class => [
            SendLoanNotifications::class,
        ],
        PinjamanApprovedByBendahara::class => [
            SendLoanNotifications::class,
        ],
        PinjamanApprovedByKetua::class => [
            SendLoanNotifications::class,
        ],
        PinjamanRejected::class => [
            SendLoanNotifications::class,
        ],
        PinjamanDisbursed::class => [
            SendLoanNotifications::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}

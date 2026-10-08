<?php

use App\Models\GateSyncLog;
use App\Models\GateSyncSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gate:sinkron-anggota')->everyMinute()
    ->when(function () {
        $set = GateSyncSetting::current();
        if (! $set->schedule_enabled) {
            return false;
        }
        $interval = max(5, (int) ($set->karyawan_interval_minutes ?? 15));
        $last = GateSyncLog::where('kind', 'karyawan')
            ->where('source', 'jadwal')->latest()->value('created_at');

        return ! $last || $last->lt(now()->subMinutes($interval));
    })->withoutOverlapping()->onOneServer();

Schedule::command('gate:sinkron-master')->dailyAt(
    GateSyncSetting::current()->master_daily_at ?? '02:00'
)->when(fn () => GateSyncSetting::current()->schedule_enabled)
    ->withoutOverlapping()->onOneServer();

<?php

namespace App\Services\Wa;

use App\Jobs\KirimWaJob;
use App\Models\Anggota;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class WaService
{
    public static function keAnggota(?Anggota $anggota, string $event, string $pesan): void
    {
        KirimWaJob::dispatch($anggota?->no_hp, $anggota?->id, $event, $pesan);
    }

    public static function keAnggotaDokumen(?Anggota $anggota, string $event, string $pesan, string $pdfBytes, string $filename): void
    {
        $path = 'wa/'.uniqid('wa_', true).'.pdf';
        Storage::disk('local')->put($path, $pdfBytes);

        KirimWaJob::dispatch($anggota?->no_hp, $anggota?->id, $event, $pesan, ['path' => $path, 'filename' => $filename]);
    }

    public static function kePengurus(string $event, string $pesan): void
    {
        User::role(['bendahara', 'ketua_koperasi'])
            ->whereHas('anggota', fn ($q) => $q->whereNotNull('no_hp'))
            ->with('anggota')
            ->get()
            ->each(fn ($user) => KirimWaJob::dispatch($user->anggota->no_hp, $user->anggota->id, $event, $pesan));
    }
}

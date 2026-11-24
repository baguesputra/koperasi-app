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

    public static function keAnggotaDokumen(?Anggota $anggota, string $event, string $pesan, string $fileBytes, string $filename, string $mimetype = 'application/pdf'): void
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION) ?: 'pdf');
        $path = 'wa/'.uniqid('wa_', true).'.'.$ext;
        Storage::disk('local')->put($path, $fileBytes);

        KirimWaJob::dispatch($anggota?->no_hp, $anggota?->id, $event, $pesan, [
            'path' => $path,
            'filename' => $filename,
            'mimetype' => $mimetype,
        ]);
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

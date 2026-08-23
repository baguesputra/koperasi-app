<?php

namespace App\Services\Pinjaman;

use App\Events\PinjamanApprovedByBendahara;
use App\Events\PinjamanApprovedByKetua;
use App\Events\PinjamanDisbursed;
use App\Events\PinjamanRejected;
use App\Models\Pinjaman;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;

class PersetujuanPinjamanService
{
    public function __construct(
        private PerhitunganBungaService $bunga,
        private JurnalKasService $jurnalKas,
    ) {}

    public function approveBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'approved_bendahara',
            'catatan_bendahara' => $catatan,
        ]);

        PinjamanApprovedByBendahara::dispatch($pinjaman, $catatan);
    }

    public function rejectBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_bendahara' => $catatan,
        ]);

        PinjamanRejected::dispatch($pinjaman, $catatan, 'bendahara');
    }

    public function approveKetua(Pinjaman $pinjaman, string $catatan): void
    {
        DB::transaction(function () use ($pinjaman, $catatan) {
            $pinjaman->update([
                'status' => 'aktif',
                'catatan_ketua' => $catatan,
                'tanggal_pencairan' => now(),
            ]);

            $this->bunga->simpanJadwal($pinjaman);

            $this->jurnalKas->catat(
                tipe: 'keluar',
                kategori: 'pencairan_pinjaman',
                kantong: 'pinjaman',
                jumlah: (float) $pinjaman->nominal,
                keterangan: "Pencairan pinjaman - {$pinjaman->anggota->nama}",
                referensiId: $pinjaman->id,
                tanggal: now()->format('Y-m-d'),
                userId: auth()->id(),
            );
        });

        PinjamanApprovedByKetua::dispatch($pinjaman, $catatan);
    }

    public function rejectKetua(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);

        PinjamanRejected::dispatch($pinjaman, $catatan, 'ketua');
    }

    public function cairBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'aktif',
            'catatan_bendahara' => $catatan,
            'tanggal_pencairan' => now(),
        ]);

        $this->bunga->simpanJadwal($pinjaman);

        $this->jurnalKas->catat(
            tipe: 'keluar',
            kategori: 'pencairan_pinjaman',
            kantong: 'pinjaman',
            jumlah: (float) $pinjaman->nominal,
            keterangan: "Pencairan pinjaman - {$pinjaman->anggota->nama}",
            referensiId: $pinjaman->id,
            tanggal: now()->format('Y-m-d'),
            userId: auth()->id(),
        );

        PinjamanDisbursed::dispatch($pinjaman, $catatan);
    }
}

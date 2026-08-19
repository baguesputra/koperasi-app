<?php

namespace App\Services\Pinjaman;

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
    }

    public function rejectBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_bendahara' => $catatan,
        ]);
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

            // Validasi saldo cukup otomatis ditangani JurnalKasService (lempar exception kalau kurang)
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
    }

    public function rejectKetua(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);
    }

    public function cairBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        if ($pinjaman->status !== 'approved_bendahara') {
            throw new RuntimeException('Pinjaman belum disetujui Bendahara.');
        }

        if ($pinjaman->tanggal_pencairan) {
            throw new RuntimeException('Pinjaman sudah dicairkan.');
        }

        DB::transaction(function () use ($pinjaman, $catatan) {
            $pinjaman->update([
                'status' => 'aktif',
                'catatan_bendahara' => $catatan ?: $pinjaman->catatan_bendahara,
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
    }
}

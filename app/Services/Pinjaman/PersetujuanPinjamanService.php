<?php

namespace App\Services\Pinjaman;

use App\Models\Pinjaman;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Wa\WaService;
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

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_disetujui_bendahara',
            'Pengajuan pinjaman Anda telah disetujui Bendahara dan sedang menunggu persetujuan Ketua.'
        );
    }

    public function rejectBendahara(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_bendahara' => $catatan,
        ]);

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_ditolak',
            'Mohon maaf, pengajuan pinjaman Anda ditolak oleh Bendahara.'.($catatan ? " Catatan: {$catatan}" : '')
        );
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

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_disetujui_ketua',
            'Selamat, pinjaman Anda sebesar Rp '.number_format((float) $pinjaman->nominal, 0, ',', '.').' telah disetujui Ketua dan dicairkan.'
        );
    }

    public function rejectKetua(Pinjaman $pinjaman, string $catatan): void
    {
        $pinjaman->update([
            'status' => 'ditolak',
            'catatan_ketua' => $catatan,
        ]);

        WaService::keAnggota(
            $pinjaman->anggota,
            'pinjaman_ditolak',
            'Mohon maaf, pengajuan pinjaman Anda ditolak oleh Ketua.'.($catatan ? " Catatan: {$catatan}" : '')
        );
    }
}

<?php

namespace App\Services\Pinjaman;

use App\Models\KasKoperasi;
use App\Models\Pinjaman;

class RingkasanPinjamanService
{
    public function ringkasan(): array
    {
        $saldoBank = (float) (KasKoperasi::query()->value('saldo_bank') ?? 0);

        $agregat = Pinjaman::query()
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->selectRaw('COUNT(*) as jumlah')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved_bendahara' THEN COALESCE(nominal_disetujui_bendahara, nominal_diminta, nominal) ELSE COALESCE(nominal_diminta, nominal) END), 0) as total")
            ->first();

        $jumlahAntrean = (int) ($agregat->jumlah ?? 0);
        $totalAntrean = (float) ($agregat->total ?? 0);

        $jumlahDisetujui = Pinjaman::query()
            ->whereNotNull('catatan_admin')
            ->whereIn('status', ['approved_admin', 'approved_bendahara', 'aktif', 'lunas'])
            ->count();

        return [
            'jumlah_antrean' => $jumlahAntrean,
            'total_antrean' => $totalAntrean,
            'jumlah_disetujui' => $jumlahDisetujui,
            'saldo_bank' => $saldoBank,
            'sisa_proyeksi' => $saldoBank - $totalAntrean,
        ];
    }
}

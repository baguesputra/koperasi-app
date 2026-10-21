<?php

namespace App\Services\Pinjaman;

use App\Models\KasKoperasi;
use App\Models\Pinjaman;

class RingkasanPinjamanService
{
    public function ringkasan(?string $peran = null): array
    {
        $saldoBank = (float) (KasKoperasi::query()->value('saldo_bank') ?? 0);

        // Agregat seluruh antrean berjalan (menggunakan formula standar)
        $agregat = Pinjaman::query()
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->selectRaw('COUNT(*) as jumlah')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved_bendahara' THEN COALESCE(nominal_disetujui_bendahara, nominal_diminta, nominal) ELSE COALESCE(nominal_diminta, nominal) END), 0) as total")
            ->first();

        $jumlahAntrean = (int) ($agregat->jumlah ?? 0);
        $totalAntrean = (float) ($agregat->total ?? 0);

        // Rincian per tahap antrean
        $admin = Pinjaman::query()
            ->where('status', 'diajukan')
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(COALESCE(nominal_diminta, nominal)), 0) as total')
            ->first();
        $adminJumlah = (int) ($admin->jumlah ?? 0);
        $adminTotal = (float) ($admin->total ?? 0);

        $bendahara = Pinjaman::query()
            ->where('status', 'approved_admin')
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(COALESCE(nominal_disetujui_bendahara, nominal_diminta, nominal)), 0) as total')
            ->first();
        $bendaharaJumlah = (int) ($bendahara->jumlah ?? 0);
        $bendaharaTotal = (float) ($bendahara->total ?? 0);

        $cairMandiri = Pinjaman::query()
            ->where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', true)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(COALESCE(nominal_disetujui_bendahara, nominal_diminta, nominal)), 0) as total')
            ->first();
        $cairMandiriJumlah = (int) ($cairMandiri->jumlah ?? 0);
        $cairMandiriTotal = (float) ($cairMandiri->total ?? 0);

        $ketua = Pinjaman::query()
            ->where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', false)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(COALESCE(nominal_disetujui_bendahara, nominal_diminta, nominal)), 0) as total')
            ->first();
        $ketuaJumlah = (int) ($ketua->jumlah ?? 0);
        $ketuaTotal = (float) ($ketua->total ?? 0);

        $sisaProyeksi = $saldoBank - $totalAntrean;
        $persenTerpakai = $saldoBank > 0
            ? round(($totalAntrean / $saldoBank) * 100, 1)
            : ($totalAntrean > 0 ? 100.0 : 0.0);

        $statusLikuiditas = 'sehat';
        if ($sisaProyeksi < 0) {
            $statusLikuiditas = 'defisit';
        } elseif ($persenTerpakai >= 80) {
            $statusLikuiditas = 'waspada';
        }

        $bebanPeran = match ($peran) {
            'admin' => [
                'label' => 'Menunggu Verifikasi Anda',
                'sublabel' => 'Pengajuan baru masuk',
                'tahap' => 'Tahap 1',
                'jumlah' => $adminJumlah,
                'total' => $adminTotal,
            ],
            'bendahara' => [
                'label' => 'Menunggu Usulan Anda',
                'sublabel' => 'Telah lolos verifikasi Admin',
                'tahap' => 'Tahap 2',
                'jumlah' => $bendaharaJumlah,
                'total' => $bendaharaTotal,
                'cair_mandiri_jumlah' => $cairMandiriJumlah,
                'cair_mandiri_total' => $cairMandiriTotal,
            ],
            'ketua' => [
                'label' => 'Menunggu Putusan Anda',
                'sublabel' => 'Telah disetujui Bendahara',
                'tahap' => 'Tahap 3',
                'jumlah' => $ketuaJumlah,
                'total' => $ketuaTotal,
            ],
            default => [
                'label' => 'Beban Antrean Berjalan',
                'sublabel' => 'Seluruh tahapan persetujuan',
                'tahap' => 'Semua',
                'jumlah' => $jumlahAntrean,
                'total' => $totalAntrean,
            ],
        };

        $jumlahDisetujui = Pinjaman::query()
            ->whereNotNull('catatan_admin')
            ->whereIn('status', ['approved_admin', 'approved_bendahara', 'aktif', 'lunas'])
            ->count();

        return [
            'peran' => $peran,
            'beban_peran' => $bebanPeran,
            'jumlah_antrean' => $jumlahAntrean,
            'total_antrean' => $totalAntrean,
            'saldo_bank' => $saldoBank,
            'sisa_proyeksi' => $sisaProyeksi,
            'persen_terpakai' => $persenTerpakai,
            'status_likuiditas' => $statusLikuiditas,
            'pipeline' => [
                'admin' => ['label' => '1. Verifikasi Admin', 'jumlah' => $adminJumlah, 'total' => $adminTotal],
                'bendahara' => ['label' => '2. Usulan Bendahara', 'jumlah' => $bendaharaJumlah, 'total' => $bendaharaTotal],
                'ketua' => ['label' => '3. Keputusan Ketua', 'jumlah' => $ketuaJumlah, 'total' => $ketuaTotal],
                'cair_mandiri' => ['label' => 'Pencairan Mandiri Ketua', 'jumlah' => $cairMandiriJumlah, 'total' => $cairMandiriTotal],
            ],
            'jumlah_disetujui' => $jumlahDisetujui,
        ];
    }
}

<?php

namespace App\Services\Pinjaman;

use App\Models\KasKoperasi;
use App\Models\Pinjaman;

class RingkasanPinjamanService
{
    public function ringkasan(?string $peran = null): array
    {
        $saldoBank = (float) (KasKoperasi::query()->value('saldo_bank') ?? 0);

        // 1 single query agregat menggantikan 5 query terpisah untuk antrean berjalan
        $rows = Pinjaman::query()
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->selectRaw('
                status,
                cair_oleh_bendahara,
                COUNT(*) as jumlah,
                COALESCE(SUM(COALESCE(nominal_diminta, nominal)), 0) as total_usulan,
                COALESCE(SUM(COALESCE(nominal_disetujui_bendahara, nominal_diminta, nominal)), 0) as total_disetujui_bendahara
            ')
            ->groupBy('status', 'cair_oleh_bendahara')
            ->get();

        $adminJumlah = 0;
        $adminTotal = 0.0;
        $bendaharaJumlah = 0;
        $bendaharaTotal = 0.0;
        $cairMandiriJumlah = 0;
        $cairMandiriTotal = 0.0;
        $ketuaJumlah = 0;
        $ketuaTotal = 0.0;
        $jumlahAntrean = 0;
        $totalAntrean = 0.0;

        foreach ($rows as $row) {
            $jml = (int) $row->jumlah;
            $jumlahAntrean += $jml;

            if ($row->status === 'diajukan') {
                $adminJumlah += $jml;
                $tot = (float) $row->total_usulan;
                $adminTotal += $tot;
                $totalAntrean += $tot;
            } elseif ($row->status === 'approved_admin') {
                $bendaharaJumlah += $jml;
                $tot = (float) $row->total_disetujui_bendahara;
                $bendaharaTotal += $tot;
                $totalAntrean += $tot;
            } elseif ($row->status === 'approved_bendahara') {
                $tot = (float) $row->total_disetujui_bendahara;
                $totalAntrean += $tot;
                if ((bool) $row->cair_oleh_bendahara) {
                    $cairMandiriJumlah += $jml;
                    $cairMandiriTotal += $tot;
                } else {
                    $ketuaJumlah += $jml;
                    $ketuaTotal += $tot;
                }
            }
        }

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

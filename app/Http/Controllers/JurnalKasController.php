<?php

namespace App\Http\Controllers;

use App\Laporan\LaporanRegistry;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JurnalKasController extends Controller
{
    public function index(Request $request): Response
    {
        $kas = KasKoperasi::firstOrFail();
        $bulanFilter = $request->input('bulan', now()->format('Y-m'));
        $kantongFilter = (string) $request->input('kantong', '');
        $kategoriFilter = (string) $request->input('kategori', '');
        $cari = trim((string) $request->input('cari', ''));

        $query = JurnalKas::query();

        if ($kantongFilter !== '' && isset(JurnalKasService::KANTONG_SALDO[$kantongFilter])) {
            $query->where('kantong', $kantongFilter);
        }

        if ($kategoriFilter !== '' && isset(LaporanRegistry::KATEGORI_LABEL[$kategoriFilter])) {
            $query->where('kategori', $kategoriFilter);
        }

        if ($bulanFilter && preg_match('/^\d{4}-\d{2}$/', $bulanFilter)) {
            [$tahun, $bulan] = explode('-', $bulanFilter);
            $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }

        if ($cari !== '') {
            $query->where('keterangan', 'like', "%{$cari}%");
        }

        $riwayat = $query->latest('tanggal')->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($j) => [
                'id' => $j->id,
                'tipe' => $j->tipe,
                'kategori' => $j->kategori,
                'kantong' => $j->kantong,
                'jumlah' => (float) $j->jumlah,
                'saldo_setelah' => (float) $j->saldo_setelah,
                'keterangan' => $j->keterangan,
                'sub_judul' => $j->sub_judul,
                'tanggal' => $j->tanggal->format('d M Y'),
            ]);

        $ringkasanQuery = clone $query;

        // Outstanding simpanan anggota aktif (sumber kebenaran: tabel simpanan).
        $totalSimpananOutstanding = (float) DB::table('simpanan')
            ->join('anggota', 'anggota.id', '=', 'simpanan.anggota_id')
            ->whereIn('simpanan.jenis', ['pokok', 'wajib'])
            ->where('anggota.status', 'aktif')
            ->sum('simpanan.jumlah');

        return Inertia::render('JurnalKas/Index', [
            'saldo' => [
                'bank' => (float) $kas->saldo_bank,
                'kas_kecil' => (float) $kas->saldo_kas_kecil,
                'outstanding' => $totalSimpananOutstanding,
            ],
            'filters' => $request->only(['kantong', 'kategori', 'bulan', 'cari']),
            'bulanFilter' => $bulanFilter,
            'kantongOptions' => JurnalKasService::KANTONG_LABEL,
            'kategoriOptions' => LaporanRegistry::KATEGORI_LABEL,
            'ringkasanPeriode' => [
                'total_masuk' => (float) (clone $ringkasanQuery)->where('tipe', 'masuk')->sum('jumlah'),
                'total_keluar' => (float) (clone $ringkasanQuery)->where('tipe', 'keluar')->sum('jumlah'),
            ],
            'riwayat' => $riwayat,
        ]);
    }
}

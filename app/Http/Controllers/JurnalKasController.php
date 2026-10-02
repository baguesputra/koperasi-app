<?php

namespace App\Http\Controllers;

use App\Laporan\LaporanRegistry;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Keuangan\JurnalKasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JurnalKasController extends Controller
{
    /**
     * Buku kas: baris fisik (bank / kas kecil) diurutkan menaik selama satu
     * bulan, plus saldo awal/akhir per akun. Transaksi non-kas dipisah agar
     * daftar utama tetap murni arus kas.
     */
    public function index(Request $request): Response
    {
        $kas = KasKoperasi::firstOrFail();
        $bulanFilter = $request->input('bulan', now()->format('Y-m'));
        $kantongFilter = (string) $request->input('kantong', '');
        $kategoriFilter = (string) $request->input('kategori', '');
        $akunFilter = (string) $request->input('akun', '');
        $cari = trim((string) $request->input('cari', ''));
        $adaFilterTambahan = $kantongFilter !== '' || $kategoriFilter !== '' || $cari !== '';

        $tanggalAwal = null;
        $tanggalAkhir = null;
        if ($bulanFilter && preg_match('/^\d{4}-\d{2}$/', $bulanFilter)) {
            $tanggalAwal = Carbon::createFromFormat('Y-m', $bulanFilter)->startOfMonth();
            $tanggalAkhir = $tanggalAwal->copy()->endOfMonth();
        }

        // Fisik = kategori bukan non-fisik; akun mempersempit ke bank / kas kecil.
        $query = $this->baseQuery($kantongFilter, $kategoriFilter, $cari, $tanggalAwal, $tanggalAkhir)
            ->whereNotIn('kategori', JurnalKasService::KATEGORI_NON_FISIK);

        if ($akunFilter === 'bank') {
            $query->where('kantong', '!=', 'kas_kecil');
        } elseif ($akunFilter === 'kas_kecil') {
            $query->where('kantong', 'kas_kecil');
        }

        $totalMasuk = (clone $query)->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (clone $query)->where('tipe', 'keluar')->sum('jumlah');

        $riwayat = $query->orderBy('tanggal')->orderBy('id')
            ->get()
            ->map(fn ($j) => $this->barisJurnal($j))
            ->values();

        $nonKas = $this->baseQuery($kantongFilter, $kategoriFilter, $cari, $tanggalAwal, $tanggalAkhir)
            ->whereIn('kategori', JurnalKasService::KATEGORI_NON_FISIK)
            ->orderBy('tanggal')->orderBy('id')
            ->limit(200)
            ->get()
            ->map(fn ($j) => $this->barisJurnal($j))
            ->values();

        $saldoAwal = [
            'bank' => $this->saldoSebelum('bank', $tanggalAwal),
            'kas_kecil' => $this->saldoSebelum('kas_kecil', $tanggalAwal),
        ];
        $saldoAkhir = [
            'bank' => $this->saldoAkhirPeriode('bank', $tanggalAwal, $tanggalAkhir, $saldoAwal['bank']),
            'kas_kecil' => $this->saldoAkhirPeriode('kas_kecil', $tanggalAwal, $tanggalAkhir, $saldoAwal['kas_kecil']),
        ];

        return Inertia::render('JurnalKas/Index', [
            'saldo' => [
                'bank' => (float) $kas->saldo_bank,
                'kas_kecil' => (float) $kas->saldo_kas_kecil,
            ],
            'filters' => $request->only(['kantong', 'kategori', 'bulan', 'cari', 'akun']),
            'bulanFilter' => $bulanFilter,
            'kantongOptions' => JurnalKasService::KANTONG_LABEL,
            'kategoriOptions' => LaporanRegistry::KATEGORI_LABEL,
            'ringkasanPeriode' => [
                'total_masuk' => (float) $totalMasuk,
                'total_keluar' => (float) $totalKeluar,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $saldoAkhir,
                'saldo_valid' => ! $adaFilterTambahan,
            ],
            'riwayat' => $riwayat,
            'nonKas' => $nonKas,
        ]);
    }

    /**
     * Query dasar baris jurnal: filter kantong/kategori/cari + rentang bulan.
     * Dipakai untuk daftar fisik maupun section non-kas.
     */
    private function baseQuery(
        string $kantongFilter,
        string $kategoriFilter,
        string $cari,
        ?Carbon $tanggalAwal,
        ?Carbon $tanggalAkhir
    ) {
        $query = JurnalKas::query();

        if ($kantongFilter !== '' && isset(JurnalKasService::KANTONG_SALDO[$kantongFilter])) {
            $query->where('kantong', $kantongFilter);
        }

        if ($kategoriFilter !== '' && isset(LaporanRegistry::KATEGORI_LABEL[$kategoriFilter])) {
            $query->where('kategori', $kategoriFilter);
        }

        if ($tanggalAwal && $tanggalAkhir) {
            $query->whereBetween('tanggal', [$tanggalAwal->toDateString(), $tanggalAkhir->toDateString()]);
        }

        if ($cari !== '') {
            $query->where('keterangan', 'like', "%{$cari}%");
        }

        return $query;
    }

    /**
     * Saldo akun sebelum periode: saldo_setelah baris fisik terakhirnya.
     */
    private function saldoSebelum(string $akun, ?Carbon $tanggalAwal): float
    {
        if (! $tanggalAwal) {
            return 0.0;
        }

        $jurnal = $this->queryAkun($akun)
            ->where('tanggal', '<', $tanggalAwal->toDateString())
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->first();

        return $jurnal ? (float) $jurnal->saldo_setelah : 0.0;
    }

    /**
     * Saldo akun di akhir periode: saldo_setelah baris fisik terakhir dalam
     * periode; fallback ke saldo awal kalau tidak ada mutasi.
     */
    private function saldoAkhirPeriode(string $akun, ?Carbon $tanggalAwal, ?Carbon $tanggalAkhir, float $saldoAwal): float
    {
        if (! $tanggalAwal || ! $tanggalAkhir) {
            return 0.0;
        }

        $jurnal = $this->queryAkun($akun)
            ->whereBetween('tanggal', [$tanggalAwal->toDateString(), $tanggalAkhir->toDateString()])
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->first();

        return $jurnal ? (float) $jurnal->saldo_setelah : $saldoAwal;
    }

    /**
     * Baris fisik satu akun (bank atau kas kecil).
     */
    private function queryAkun(string $akun)
    {
        $query = JurnalKas::whereNotIn('kategori', JurnalKasService::KATEGORI_NON_FISIK);

        return $akun === 'kas_kecil'
            ? $query->where('kantong', 'kas_kecil')
            : $query->where('kantong', '!=', 'kas_kecil');
    }

    private function barisJurnal(JurnalKas $j): array
    {
        return [
            'id' => $j->id,
            'tipe' => $j->tipe,
            'kategori' => $j->kategori,
            'kantong' => $j->kantong,
            'akun' => JurnalKasService::kanalFisik($j->kantong, $j->kategori),
            'no_bukti' => $j->no_bukti,
            'jumlah' => (float) $j->jumlah,
            'saldo_setelah' => (float) $j->saldo_setelah,
            'keterangan' => $j->keterangan,
            'sub_judul' => $j->sub_judul,
            'tanggal' => $j->tanggal->format('d M Y'),
        ];
    }
}

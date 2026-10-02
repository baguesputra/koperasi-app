<?php

namespace App\Http\Controllers;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class KasKoperasiController extends Controller
{
    public function __construct(
        private JurnalKasService $jurnalKas,
    ) {}

    /**
     * Konsep Kas Tunggal: satu daftar arus kas gabungan semua kantong +
     * filter bulan, kanal fisik (bank/kas kecil/audit), dan pencarian.
     * Badge kanal + kantong di tiap baris jadi penanda jenis mutasi.
     */
    public function index(Request $request): Response
    {
        $kas = KasKoperasi::firstOrFail();
        $bulanFilter = $request->input('bulan', now()->format('Y-m'));
        $kanalFilter = (string) $request->input('kanal', '');
        $cari = trim((string) $request->input('cari', ''));

        $query = JurnalKas::query();

        if ($bulanFilter && preg_match('/^\d{4}-\d{2}$/', $bulanFilter)) {
            [$tahun, $bulan] = explode('-', $bulanFilter);
            $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('keterangan', 'like', "%{$cari}%")
                    ->orWhere('sub_judul', 'like', "%{$cari}%");
            });
        }

        if (in_array($kanalFilter, ['bank', 'kas_kecil', 'audit'], true)) {
            $query = $this->denganFilterKanal($query, $kanalFilter);
        }

        $riwayat = (clone $query)->latest('tanggal')->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($j) => [
                'id' => $j->id,
                'tipe' => $j->tipe,
                'kategori' => $j->kategori,
                'kantong' => $j->kantong,
                'kanal' => JurnalKasService::kanalFisik($j->kantong, $j->kategori),
                'jumlah' => (float) $j->jumlah,
                'saldo_setelah' => (float) $j->saldo_setelah,
                'keterangan' => $j->keterangan,
                'sub_judul' => $j->sub_judul,
                'tanggal' => $j->tanggal->format('d M Y'),
                'hari' => $j->tanggal->translatedFormat('l'),
                'rute_asal' => $this->ruteAsal($j),
            ]);

        return Inertia::render('KasKoperasi/Index', [
            'saldoBank' => (float) $kas->saldo_bank,
            'saldoKasKecil' => (float) $kas->saldo_kas_kecil,
            'bulanFilter' => $bulanFilter,
            'filters' => ['kanal' => $kanalFilter, 'cari' => $cari],
            'ringkasanPeriode' => [
                'total_masuk' => (float) (clone $query)->where('tipe', 'masuk')->sum('jumlah'),
                'total_keluar' => (float) (clone $query)->where('tipe', 'keluar')->sum('jumlah'),
            ],
            'ringkasanKanal' => $this->ringkasanKanal($query),
            'riwayat' => $riwayat,
        ]);
    }

    /**
     * Salinan query dengan filter kanal fisik: derivasi dari kantong +
     * KATEGORI_NON_FISIK, diterjemahkan jadi kondisi WHERE biasa
     * (lihat JurnalKasService::kanalFisik).
     */
    private function denganFilterKanal($query, string $kanal)
    {
        $q = clone $query;

        if ($kanal === 'audit') {
            $q->whereIn('kategori', JurnalKasService::KATEGORI_NON_FISIK);
        } elseif ($kanal === 'kas_kecil') {
            $q->where('kantong', 'kas_kecil')
                ->whereNotIn('kategori', JurnalKasService::KATEGORI_NON_FISIK);
        } else {
            $q->where('kantong', '!=', 'kas_kecil')
                ->whereNotIn('kategori', JurnalKasService::KATEGORI_NON_FISIK);
        }

        return $q;
    }

    /**
     * Total masuk/keluar per kanal dalam query yang sudah terfilter,
     * untuk rincian ringkasan di hero.
     */
    private function ringkasanKanal($query): array
    {
        $hasil = [];
        foreach (['bank', 'kas_kecil', 'audit'] as $kanal) {
            $perKanal = $this->denganFilterKanal($query, $kanal);
            $hasil[$kanal] = [
                'masuk' => (float) (clone $perKanal)->where('tipe', 'masuk')->sum('jumlah'),
                'keluar' => (float) (clone $perKanal)->where('tipe', 'keluar')->sum('jumlah'),
            ];
        }

        return $hasil;
    }

    /**
     * Rute halaman asal transaksi, hanya untuk kategori yang jenis
     * referensinya pasti (pinjaman id / anggota id) — selainnya null.
     */
    private function ruteAsal(JurnalKas $j): ?array
    {
        if ($j->referensi_id === null) {
            return null;
        }

        return match ($j->kategori) {
            'pencairan_pinjaman' => ['nama' => 'pinjaman.show', 'id' => $j->referensi_id, 'izin' => 'pinjaman.lihat'],
            'simpanan_pokok_masuk', 'simpanan_wajib_masuk' => ['nama' => 'simpanan.show', 'id' => $j->referensi_id, 'izin' => 'simpanan.lihat'],
            default => null,
        };
    }

    public function topup(Request $request)
    {
        $request->validate([
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        // Konsep Kas Tunggal: topup selalu masuk Bank.
        $this->jurnalKas->catat(
            tipe: 'masuk',
            kategori: 'topup_bulanan',
            kantong: 'bank',
            jumlah: $request->jumlah,
            keterangan: $request->keterangan ?: 'Topup saldo koperasi',
            referensiId: null,
            tanggal: now()->format('Y-m-d'),
            userId: auth()->id(),
        );

        return back()->with('status', 'Saldo berhasil ditambahkan.');
    }

    public function sisihKasKecil(Request $request)
    {
        $request->validate([
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->jurnalKas->sisihKasKecil(
                jumlah: (float) $request->jumlah,
                keterangan: $request->keterangan ?: 'Sisihkan kas kecil dari bank',
                tanggal: now()->format('Y-m-d'),
                userId: auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['jumlah' => $e->getMessage()]);
        }

        return back()->with('status', 'Kas kecil berhasil disisihkan.');
    }
}

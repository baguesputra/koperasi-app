<?php

namespace App\Http\Controllers;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pengeluaran;
use App\Services\Keuangan\JurnalKasService;
use Carbon\Carbon;
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
     * filter bulan, kanal fisik (bank/kas kecil), dan pencarian.
     * Badge kanal + kantong di tiap baris jadi penanda jenis mutasi.
     */
    public function index(Request $request): Response
    {
        $bulanFilter = (string) $request->input('bulan', now()->format('Y-m'));
        $kanalFilter = (string) $request->input('kanal', '');
        $tipeFilter = (string) $request->input('tipe', '');
        $tanggalFilter = (string) $request->input('tanggal', '');
        $cari = trim((string) $request->input('cari', ''));
        $tipePengeluaranFilter = (string) $request->input('tipe_pengeluaran', '');

        $periodeAwal = null;
        $periodeAkhir = null;

        // Jika filter tanggal diisi, selaraskan bulanFilter dengan bulan dari tanggal tersebut
        if ($tanggalFilter && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalFilter)) {
            $periodeAwal = Carbon::createFromFormat('Y-m-d', $tanggalFilter)->startOfDay();
            $periodeAkhir = $periodeAwal->copy()->endOfDay();
            $bulanFilter = $periodeAwal->format('Y-m');
        } elseif ($bulanFilter && preg_match('/^\d{4}-\d{2}$/', $bulanFilter)) {
            $periodeAwal = Carbon::createFromFormat('Y-m', $bulanFilter)->startOfMonth();
            $periodeAkhir = $periodeAwal->copy()->endOfMonth();
        }

        $query = JurnalKas::query();

        if ($periodeAwal && $periodeAkhir) {
            $query->whereBetween('tanggal', [$periodeAwal->toDateTimeString(), $periodeAkhir->toDateTimeString()]);
        }

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('keterangan', 'like', "%{$cari}%")
                    ->orWhere('sub_judul', 'like', "%{$cari}%");
            });
        }

        if (in_array($kanalFilter, ['bank', 'kas_kecil'], true)) {
            $query = $this->denganFilterKanal($query, $kanalFilter);
        }

        if (in_array($tipeFilter, ['masuk', 'keluar'], true)) {
            $query->where('tipe', $tipeFilter);
        }

        if ($tipePengeluaranFilter && in_array($tipePengeluaranFilter, Pengeluaran::TIPE, true)) {
            $pengeluaranIds = Pengeluaran::where('tipe', $tipePengeluaranFilter)->pluck('id');
            $query->whereIn('kategori', ['pengeluaran_koperasi', 'pengeluaran_dana_sosial'])
                ->whereIn('referensi_id', $pengeluaranIds);
        }

        $adaFilterTransaksi = $cari !== ''
            || in_array($tipeFilter, ['masuk', 'keluar'], true)
            || ($tipePengeluaranFilter !== '' && in_array($tipePengeluaranFilter, Pengeluaran::TIPE, true));

        $riwayat = (clone $query)
            ->with(['pengeluaran:id,tipe,akun'])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($j) => [
                'id' => $j->id,
                'tipe' => $j->tipe,
                'kategori' => $j->kategori,
                'sumber_topup' => $j->sumber_topup,
                'tipe_pengeluaran' => $j->pengeluaran?->tipe,
                'tipe_pengeluaran_label' => $j->pengeluaran ? (Pengeluaran::TIPE_LABEL[$j->pengeluaran->tipe] ?? $j->pengeluaran->tipe) : null,
                'kantong' => $j->kantong,
                'kanal' => JurnalKasService::kanalFisik($j->kantong, $j->kategori),
                'jumlah' => (float) $j->jumlah,
                'saldo_setelah' => (float) $j->saldo_setelah,
                'keterangan' => $j->keterangan,
                'sub_judul' => $j->sub_judul,
                'tanggal' => $j->tanggal->format('d M Y'),
                'tanggalIso' => $j->tanggal->format('Y-m-d'),
                'hari' => $j->tanggal->translatedFormat('l'),
                'rute_asal' => $this->ruteAsal($j),
            ]);

        // 1 query agregat GROUP BY (ganti 8x sum): kantong+kategori+tipe, kanal dihitung di PHP
        $agregat = (clone $query)
            ->selectRaw('kantong, kategori, tipe, SUM(jumlah) as total')
            ->groupBy('kantong', 'kategori', 'tipe')
            ->get();

        $totalMasuk = 0.0;
        $totalKeluar = 0.0;
        $ringkasanKanal = ['bank' => ['masuk' => 0.0, 'keluar' => 0.0], 'kas_kecil' => ['masuk' => 0.0, 'keluar' => 0.0]];
        foreach ($agregat as $row) {
            $total = (float) $row->total;
            if ($row->tipe === 'masuk') {
                $totalMasuk += $total;
            } else {
                $totalKeluar += $total;
            }
            $kanal = JurnalKasService::kanalFisik($row->kantong, $row->kategori);
            $ringkasanKanal[$kanal][$row->tipe] += $total;
        }

        // Hitung agregat harian HANYA untuk tanggal yang ada di halaman aktif ($riwayat)
        $tanggalDiHalaman = collect($riwayat->items())->pluck('tanggalIso')->unique()->values();

        $ringkasanPerTanggal = [];
        if ($tanggalDiHalaman->isNotEmpty()) {
            $agregatTanggal = (clone $query)
                ->where(function ($q) use ($tanggalDiHalaman) {
                    foreach ($tanggalDiHalaman as $tgl) {
                        $q->orWhereBetween('tanggal', ["{$tgl} 00:00:00", "{$tgl} 23:59:59"]);
                    }
                })
                ->selectRaw('DATE(tanggal) as tanggal_hari, tipe, SUM(jumlah) as total')
                ->groupBy('tanggal_hari', 'tipe')
                ->get();

            foreach ($agregatTanggal as $row) {
                $tgl = (string) $row->tanggal_hari;
                $kunciTgl = substr($tgl, 0, 10);
                if (! isset($ringkasanPerTanggal[$kunciTgl])) {
                    $ringkasanPerTanggal[$kunciTgl] = ['masuk' => 0.0, 'keluar' => 0.0];
                }
                $ringkasanPerTanggal[$kunciTgl][$row->tipe] = (float) $row->total;
            }
        }

        $saldoAwalKanal = [
            'bank' => $periodeAwal ? $this->saldoKanal('bank', $periodeAwal, '<') : 0.0,
            'kas_kecil' => $periodeAwal ? $this->saldoKanal('kas_kecil', $periodeAwal, '<') : 0.0,
        ];
        $saldoAkhirKanal = [
            'bank' => $periodeAkhir ? $this->saldoKanal('bank', $periodeAkhir, '<=') : 0.0,
            'kas_kecil' => $periodeAkhir ? $this->saldoKanal('kas_kecil', $periodeAkhir, '<=') : 0.0,
        ];

        $kanalSaldo = in_array($kanalFilter, ['bank', 'kas_kecil'], true) ? $kanalFilter : null;
        $saldoAwal = $kanalSaldo ? $saldoAwalKanal[$kanalSaldo] : array_sum($saldoAwalKanal);
        $saldoAkhir = $kanalSaldo ? $saldoAkhirKanal[$kanalSaldo] : array_sum($saldoAkhirKanal);

        // Samakan saldo baris dengan mutasi sebenarnya, bukan saldo_setelah
        // historis yang bisa tertinggal dari data jurnal.
        if (! $adaFilterTransaksi && ! $riwayat->isEmpty()) {
            $halamanPertama = $riwayat->items()[0];

            $sebelumHalaman = (clone $query)
                ->where(function ($q) use ($halamanPertama) {
                    $q->whereDate('tanggal', '<', $halamanPertama['tanggalIso'])
                        ->orWhere(function ($q) use ($halamanPertama) {
                            $q->whereDate('tanggal', $halamanPertama['tanggalIso'])
                                ->where('id', '<', $halamanPertama['id']);
                        });
                })
                ->selectRaw("
                    COALESCE(SUM(CASE
                        WHEN kantong = 'kas_kecil' AND tipe = 'masuk' THEN jumlah
                        WHEN kantong = 'kas_kecil' THEN -jumlah
                        ELSE 0 END), 0) as kas_kecil,
                    COALESCE(SUM(CASE
                        WHEN kantong != 'kas_kecil' AND tipe = 'masuk' THEN jumlah
                        WHEN kantong != 'kas_kecil' THEN -jumlah
                        ELSE 0 END), 0) as bank
                ")
                ->first();

            $saldoJalan = [
                'bank' => $saldoAwalKanal['bank'] + (float) ($sebelumHalaman->bank ?? 0),
                'kas_kecil' => $saldoAwalKanal['kas_kecil'] + (float) ($sebelumHalaman->kas_kecil ?? 0),
            ];

            $riwayat->through(function (array $row) use (&$saldoJalan) {
                $saldoJalan[$row['kanal']] += $row['tipe'] === 'masuk' ? $row['jumlah'] : -$row['jumlah'];
                $row['saldo_setelah'] = $saldoJalan[$row['kanal']];

                return $row;
            });
        }

        $ambilKas = function () {
            static $kas = null;
            return $kas ??= KasKoperasi::firstOrFail();
        };

        return Inertia::render('KasKoperasi/Index', [
            'saldoBank' => fn () => (float) $ambilKas()->saldo_bank,
            'saldoKasKecil' => fn () => (float) $ambilKas()->saldo_kas_kecil,
            'bulanFilter' => $bulanFilter,
            'filters' => [
                'kanal' => $kanalFilter,
                'cari' => $cari,
                'tipe' => $tipeFilter,
                'tanggal' => $tanggalFilter,
                'tipe_pengeluaran' => $tipePengeluaranFilter,
            ],
            'tipePengeluaranList' => Pengeluaran::TIPE_LABEL,
            'akunPengeluaranList' => Pengeluaran::AKUN_LABEL,
            'ringkasanPeriode' => [
                'total_masuk' => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $saldoAkhir,
                'saldo_valid' => ! $adaFilterTransaksi,
            ],
            'ringkasanKanal' => $ringkasanKanal,
            'ringkasanPerTanggal' => $ringkasanPerTanggal,
            'riwayat' => $riwayat,
        ]);
    }

    /**
     * Salinan query dengan filter kanal fisik (bank / kas kecil).
     */
    private function denganFilterKanal($query, string $kanal)
    {
        $q = clone $query;

        if ($kanal === 'kas_kecil') {
            $q->where('kantong', 'kas_kecil');
        } else {
            $q->where('kantong', '!=', 'kas_kecil');
        }

        return $q;
    }

    /**
     * Saldo satu kanal fisik sampai batas tanggal (inklusif/eksklusif).
     * Dihitung dari mutasi, bukan saldo_setelah historis.
     */
    private function saldoKanal(string $kanal, Carbon $batas, string $operator): float
    {
        $query = JurnalKas::query()->where('tanggal', $operator, $batas->toDateTimeString());

        return (float) $this->denganFilterKanal($query, $kanal)
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE -jumlah END), 0) as saldo")
            ->value('saldo');
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
            'sumber_topup' => ['required', 'in:modal,bunga_bank'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'lain_lain' => ['nullable', 'boolean'],
            'keterangan_lain' => ['nullable', 'required_if:lain_lain,true', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date'],
        ]);

        $keterangan = $request->keterangan ?: 'Topup saldo koperasi';

        if ($request->boolean('lain_lain')) {
            $keterangan = '[Lain-lain] '.$request->keterangan_lain.($request->keterangan ? ' — '.$request->keterangan : '');
        }

        $tanggal = $request->filled('tanggal') ? $request->tanggal : now()->format('Y-m-d');

        // Topup selalu masuk Bank, dengan sumber modal / bunga bank.
        $this->jurnalKas->catat(
            tipe: 'masuk',
            kategori: 'topup_bulanan',
            kantong: 'bank',
            jumlah: $request->jumlah,
            keterangan: $keterangan,
            referensiId: null,
            tanggal: $tanggal,
            userId: auth()->id(),
            sumberTopup: $request->sumber_topup,
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

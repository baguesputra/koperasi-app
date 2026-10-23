<?php

namespace App\Http\Controllers;

use App\Exports\ShuExport;
use App\Models\Anggota;
use App\Models\ShuDetail;
use App\Models\ShuKomponenPembayaran;
use App\Models\ShuPeriode;
use App\Services\Keuangan\ShuService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ShuController extends Controller
{
    public function __construct(private ShuService $service) {}

    public function index(Request $request): Response
    {
        $query = ShuPeriode::withCount('detail')->orderByDesc('tahun_buku');

        if ($request->filled('status') && in_array($request->string('status')->value(), ['draft', 'diajukan', 'final'], true)) {
            $query->where('status', $request->string('status')->value());
        }

        $periode = $query->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'tahun_buku' => $p->tahun_buku,
                'total_shu' => (float) $p->total_shu,
                'status' => $p->status,
                'jumlah_anggota' => $p->detail_count,
                'finalized_at' => $p->finalized_at?->format('d M Y H:i'),
            ]);

        return Inertia::render('Shu/Index', [
            'periode' => $periode,
            'filters' => $request->only(['status']),
            'persenDefault' => ShuService::PERSEN_DEFAULT,
            'tahunSaran' => (int) now()->format('Y'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tahun_buku' => ['required', 'integer', 'min:2000', 'max:2100'],
            'total_shu' => ['nullable', 'numeric', 'min:0'],
            'persen' => ['nullable', 'array'],
            'persen.*' => ['numeric', 'min:0', 'max:1'],
            'komponen_custom' => ['nullable', 'array', 'max:20'],
            'komponen_custom.*.label' => ['required_with:komponen_custom', 'string', 'max:100'],
            'komponen_custom.*.persen' => ['required_with:komponen_custom.*.label', 'numeric', 'min:0', 'max:1'],
        ]);

        try {
            $periode = $this->service->buatPeriode(
                (int) $data['tahun_buku'],
                $data['persen'] ?? null,
                isset($data['total_shu']) ? (float) $data['total_shu'] : null,
                auth()->id(),
                $data['komponen_custom'] ?? [],
            );
            // Langsung hitung supaya tab Distribusi/Ringkasan terisi saat dibuka.
            $ringkasan = $this->service->hitung($periode, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['tahun_buku' => $e->getMessage(), 'persen' => $e->getMessage(), 'komponen_custom' => $e->getMessage()]);
        }

        return redirect()->route('shu.show', ['shu' => $periode->id, 'tab' => 'distribusi'])
            ->with('status', "Periode SHU {$periode->tahun_buku} dibuat, {$ringkasan['anggota']} anggota terhitung.");
    }

    public function show(Request $request, ShuPeriode $shu): Response
    {
        $query = ShuDetail::with('anggota:id,nama,no_karyawan,cabang')
            ->where('shu_periode_id', $shu->id);

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->whereHas('anggota', fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('no_karyawan', 'like', "%{$cari}%"));
        }

        if ($request->filled('bayar') && in_array($request->string('bayar')->value(), ['belum', 'sudah'], true)) {
            $query->where('status_bayar', $request->string('bayar')->value());
        }

        $bolehSort = ['no', 'nama', 'simp_wajib', 'simp_pokok', 'modal', 'transaksi', 'shu_modal', 'shu_transaksi', 'shu_bruto', 'shu_bersih'];
        $sort = in_array($request->string('sort')->value(), $bolehSort, true) ? $request->string('sort')->value() : 'no';
        $arah = $request->filled('arah')
            ? ($request->string('arah')->value() === 'desc' ? 'desc' : 'asc')
            : (in_array($sort, ['no', 'nama'], true) ? 'asc' : 'desc');

        if (in_array($sort, ['no', 'nama'], true)) {
            $query->join('anggota', 'anggota.id', '=', 'shu_detail.anggota_id')
                ->select('shu_detail.*');
            $query->orderBy($sort === 'nama' ? 'anggota.nama' : 'anggota.id', $arah);
        } else {
            $query->orderBy($sort, $arah);
        }

        // Query agregat terpisah (tanpa select kolom/sort) agar lolos
        // ONLY_FULL_GROUP_BY MySQL: agregat murni tanpa kolom non-agregat.
        $sums = ShuDetail::where('shu_periode_id', $shu->id)
            ->when($request->filled('cari'), function ($q) use ($request) {
                $cari = $request->string('cari');
                $q->whereHas('anggota', fn ($r) => $r
                    ->where('nama', 'like', "%{$cari}%")
                    ->orWhere('no_karyawan', 'like', "%{$cari}%"));
            })
            ->when(
                $request->filled('bayar') && in_array($request->string('bayar')->value(), ['belum', 'sudah'], true),
                fn ($q) => $q->where('status_bayar', $request->string('bayar')->value())
            )
            ->selectRaw('COALESCE(SUM(simp_wajib),0) as wajib,
                COALESCE(SUM(simp_pokok),0) as pokok,
                COALESCE(SUM(modal),0) as modal,
                COALESCE(SUM(transaksi),0) as transaksi,
                COALESCE(SUM(shu_bruto),0) as bruto,
                COALESCE(SUM(shu_bersih),0) as bersih')
            ->first();

        $detail = $query->orderBy('shu_detail.id')
            ->paginate(20)
            ->withQueryString();

        $no = $detail->firstItem() ?? 1;
        $detail->setCollection($detail->getCollection()->map(function ($d) use (&$no) {
            return [
                'no' => $no++,
                'id' => $d->id,
                'nama' => $d->anggota->nama,
                'no_karyawan' => $d->anggota->no_karyawan,
                'cabang' => $d->anggota->cabang,
                'simp_pokok' => (float) $d->simp_pokok,
                'simp_wajib' => (float) $d->simp_wajib,
                'modal' => (float) $d->modal,
                'transaksi' => (float) $d->transaksi,
                'shu_modal' => (float) $d->shu_modal,
                'shu_transaksi' => (float) $d->shu_transaksi,
                'shu_bruto' => (float) $d->shu_bruto,
                'shu_bersih' => (float) $d->shu_bersih,
                'status_bayar' => $d->status_bayar,
                'dibayar_pada' => $d->dibayar_pada?->format('d M Y H:i'),
            ];
        }));

        $totalLaba = $this->service->totalShuDariLaba($shu->tahun_buku);
        $shu->loadMissing('customKomponen');

        return Inertia::render('Shu/Show', [
            'periode' => [
                'id' => $shu->id,
                'tahun_buku' => $shu->tahun_buku,
                'total_shu' => (float) $shu->total_shu,
                'total_laba' => $totalLaba,
                'status' => $shu->status,
                'is_final' => $shu->isFinal(),
                'persen' => collect(ShuPeriode::KOMPONEN)->mapWithKeys(fn ($kolom) => [$kolom => (float) $shu->{$kolom}]),
                'komponen_custom' => $shu->daftarCustom(),
                'pengurus_rincian' => $shu->pengurus_rincian ?? [],
                'pengawas_rincian' => $shu->pengawas_rincian ?? [],
                'finalized_at' => $shu->finalized_at?->format('d M Y H:i'),
                'diajukan_at' => $shu->diajukan_at?->format('d M Y H:i'),
            ],
            'ringkasan' => $this->service->ringkasan($shu),
            'komponen_bayar' => $this->service->daftarPembayaranKomponen($shu),
            'detail' => $detail,
            'daftarAnggota' => \Illuminate\Support\Facades\Cache::remember(
                'daftar_anggota_aktif_shu',
                300,
                fn () => Anggota::where('status', 'aktif')->orderBy('nama')->get(['id', 'nama', 'no_karyawan', 'cabang'])
            ),
            'sums' => [
                'wajib' => (float) ($sums->wajib ?? 0),
                'pokok' => (float) ($sums->pokok ?? 0),
                'modal' => (float) ($sums->modal ?? 0),
                'transaksi' => (float) ($sums->transaksi ?? 0),
                'bruto' => (float) ($sums->bruto ?? 0),
                'bersih' => (float) ($sums->bersih ?? 0),
            ],
            'filters' => $request->only(['cari', 'bayar', 'sort', 'arah']),
        ]);
    }

    public function updatePersen(Request $request, ShuPeriode $shu)
    {
        $data = $request->validate([
            'persen' => ['required', 'array'],
            'persen.*' => ['numeric', 'min:0', 'max:1'],
            'komponen_custom' => ['nullable', 'array', 'max:20'],
            'komponen_custom.*.label' => ['required_with:komponen_custom', 'string', 'max:100'],
            'komponen_custom.*.persen' => ['required_with:komponen_custom.*.label', 'numeric', 'min:0', 'max:1'],
        ]);

        try {
            $shu = $this->service->ubahPersen($shu, $data['persen'], auth()->id(), $data['komponen_custom'] ?? null);
            // Simpan persen sekaligus hitung ulang (ala Excel: ubah % → angka ikut).
            $ringkasan = $this->service->hitung($shu, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['persen' => $e->getMessage(), 'komponen_custom' => $e->getMessage()]);
        }

        return back()->with('status', "Persentase disimpan & rincian dihitung ulang ({$ringkasan['anggota']} anggota).");
    }

    public function hitung(Request $request, ShuPeriode $shu)
    {
        try {
            $ringkasan = $this->service->hitung($shu, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['hitung' => $e->getMessage()]);
        }

        return back()->with('status', "Perhitungan selesai: {$ringkasan['anggota']} anggota.");
    }

    public function rincianPengelola(Request $request, ShuPeriode $shu)
    {
        $data = $request->validate([
            'pengurus' => ['nullable', 'array'],
            'pengurus.*.anggota_id' => ['nullable', 'integer', 'exists:anggota,id'],
            'pengurus.*.nama' => ['nullable', 'string', 'max:100'],
            'pengurus.*.jabatan' => ['nullable', 'string', 'max:100'],
            'pengurus.*.nominal' => ['nullable', 'numeric', 'min:1'],
            'pengawas' => ['nullable', 'array'],
            'pengawas.*.nama' => ['required_with:pengawas.*.nominal', 'string', 'max:100'],
            'pengawas.*.nominal' => ['required_with:pengawas.*.nama', 'numeric', 'min:1'],
        ]);

        try {
            $this->service->simpanRincianPengelola($shu, $data['pengurus'] ?? null, $data['pengawas'] ?? null, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['rincian' => $e->getMessage()]);
        }

        return back()->with('status', 'Rincian pengurus/pengawas disimpan.');
    }

    public function finalisasi(Request $request, ShuPeriode $shu)
    {
        try {
            $this->service->finalisasi($shu, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['final' => $e->getMessage()]);
        }

        return back()->with('status', "SHU {$shu->tahun_buku} difinalisasi dan dikunci.");
    }

    public function ajukan(Request $request, ShuPeriode $shu)
    {
        try {
            $this->service->ajukan($shu, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['aju' => $e->getMessage()]);
        }

        return back()->with('status', "SHU {$shu->tahun_buku} diajukan untuk dibahas.");
    }

    public function kembalikan(Request $request, ShuPeriode $shu)
    {
        try {
            $this->service->kembalikanKeDraft($shu, auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['aju' => $e->getMessage()]);
        }

        return back()->with('status', "SHU {$shu->tahun_buku} dikembalikan ke draft.");
    }

    public function bayar(Request $request, ShuDetail $detail)
    {
        $data = $request->validate([
            'akun' => ['required', 'in:bank,kas_kecil'],
            'tanggal' => ['required', 'date'],
        ]);

        try {
            $this->service->bayarDetail($detail, $data['akun'], $data['tanggal'], auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['bayar' => $e->getMessage()]);
        }

        return back()->with('status', 'SHU anggota dibayar.');
    }

    public function bayarSemua(Request $request, ShuPeriode $shu)
    {
        $data = $request->validate([
            'akun' => ['required', 'in:bank,kas_kecil'],
            'tanggal' => ['required', 'date'],
        ]);

        try {
            $n = $this->service->bayarSemua($shu, $data['akun'], $data['tanggal'], auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['bayar' => $e->getMessage()]);
        }

        return back()->with('status', "{$n} SHU anggota dibayar.");
    }

    public function bayarKomponen(Request $request, ShuKomponenPembayaran $bayar)
    {
        $data = $request->validate([
            'akun' => ['required', 'in:bank,kas_kecil'],
            'tanggal' => ['required', 'date'],
        ]);

        try {
            $this->service->bayarKomponen($bayar, $data['akun'], $data['tanggal'], auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['bayar' => $e->getMessage()]);
        }

        return back()->with('status', "Pembayaran {$bayar->nama_komponen} berhasil dicatat di kas koperasi.");
    }

    public function bayarSemuaKomponen(Request $request, ShuPeriode $shu)
    {
        $data = $request->validate([
            'akun' => ['required', 'in:bank,kas_kecil'],
            'tanggal' => ['required', 'date'],
        ]);

        try {
            $n = $this->service->bayarSemuaKomponen($shu, $data['akun'], $data['tanggal'], auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['bayar' => $e->getMessage()]);
        }

        return back()->with('status', "{$n} komponen SHU berhasil dibayar dan dicatat di kas koperasi.");
    }

    public function export(ShuPeriode $shu)
    {
        return Excel::download(
            new ShuExport($shu),
            "Komponen Pembagian SHU {$shu->tahun_buku}.xlsx"
        );
    }
}

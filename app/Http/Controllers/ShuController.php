<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Models\ShuDetail;
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

    public function index(): Response
    {
        $periode = ShuPeriode::withCount('detail')
            ->orderByDesc('tahun_buku')
            ->get()
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
        ]);

        try {
            $periode = $this->service->buatPeriode(
                (int) $data['tahun_buku'],
                $data['persen'] ?? null,
                isset($data['total_shu']) ? (float) $data['total_shu'] : null,
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['tahun_buku' => $e->getMessage()]);
        }

        return redirect()->route('shu.show', $periode)->with('status', "Periode SHU {$periode->tahun_buku} dibuat.");
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

        $detail = $query->orderByDesc('shu_bersih')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($d) => [
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
            ]);

        $totalLaba = $this->service->totalShuDariLaba($shu->tahun_buku);

        return Inertia::render('Shu/Show', [
            'periode' => [
                'id' => $shu->id,
                'tahun_buku' => $shu->tahun_buku,
                'total_shu' => (float) $shu->total_shu,
                'total_laba' => $totalLaba,
                'status' => $shu->status,
                'is_final' => $shu->isFinal(),
                'persen' => collect(ShuPeriode::KOMPONEN)->mapWithKeys(fn ($kolom) => [$kolom => (float) $shu->{$kolom}]),
                'pengurus_rincian' => $shu->pengurus_rincian ?? [],
                'pengawas_rincian' => $shu->pengawas_rincian ?? [],
                'finalized_at' => $shu->finalized_at?->format('d M Y H:i'),
            ],
            'ringkasan' => $this->service->ringkasan($shu),
            'detail' => $detail,
            'filters' => $request->only(['cari', 'bayar']),
        ]);
    }

    public function updatePersen(Request $request, ShuPeriode $shu)
    {
        $data = $request->validate([
            'persen' => ['required', 'array'],
            'persen.*' => ['numeric', 'min:0', 'max:1'],
        ]);

        try {
            $this->service->ubahPersen($shu, $data['persen'], auth()->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['persen' => $e->getMessage()]);
        }

        return back()->with('status', 'Persentase komponen diperbarui. Jalankan Hitung Ulang untuk menerapkan.');
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
            'pengurus.*.nama' => ['required_with:pengurus.*.nominal', 'string', 'max:100'],
            'pengurus.*.nominal' => ['required_with:pengurus.*.nama', 'numeric', 'min:1'],
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

    public function export(ShuPeriode $shu)
    {
        $rows = ShuDetail::with('anggota:id,nama,no_karyawan')
            ->where('shu_periode_id', $shu->id)
            ->orderByDesc('shu_bersih')
            ->get()
            ->map(fn ($d, $i) => [
                $i + 1,
                $d->anggota->no_karyawan,
                $d->anggota->nama,
                (float) $d->simp_wajib,
                (float) $d->simp_pokok,
                (float) $d->modal,
                (float) $d->transaksi,
                (float) $d->shu_modal,
                (float) $d->shu_transaksi,
                (float) $d->shu_bruto,
                (float) $d->shu_bersih,
                $d->status_bayar,
            ])
            ->all();

        $ringkasan = $this->service->ringkasan($shu);

        $baris = [
            ["DISTRIBUSI SHU PER ANGGOTA — TAHUN BUKU {$shu->tahun_buku}"],
            ['No', 'No Karyawan', 'Nama', 'Simp Wajib', 'Simp Pokok', 'Modal', 'Transaksi', 'SHU Modal', 'SHU Transaksi', 'Jumlah SHU', 'SHU Diserahkan', 'Status Bayar'],
            ...$rows,
            [],
            ['Total SHU', (float) $shu->total_shu],
            ['Pool anggota', $ringkasan['pool_anggota']],
            ['Total bruto', $ringkasan['total_bruto']],
            ['Total diserahkan', $ringkasan['total_bersih']],
            ['Sisa belum diserahkan (pembulatan)', $ringkasan['belum_diserahkan']],
        ];

        return Excel::download(new ArrayExport($baris), "distribusi-shu-{$shu->tahun_buku}.xlsx");
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Exports\TemplateMigrasiPinjamanExport;
use App\Exports\TemplateMigrasiSimpananExport;
use App\Imports\PinjamanMigrasiImport;
use App\Imports\SimpananMigrasiImport;
use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Services\Migrasi\MigrasiPinjamanService;
use App\Services\Migrasi\MigrasiSimpananService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class MigrasiController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Migrasi/Index');
    }

    public function templatePinjaman()
    {
        return Excel::download(new TemplateMigrasiPinjamanExport, 'template-migrasi-pinjaman.xlsx');
    }

    public function templateSimpanan()
    {
        return Excel::download(new TemplateMigrasiSimpananExport, 'template-migrasi-simpanan.xlsx');
    }

    public function importPinjaman(Request $request, MigrasiPinjamanService $migrasi)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $import = new PinjamanMigrasiImport($migrasi);
        Excel::import($import, $request->file('file'));

        return back()->with([
            'importBerhasil' => $import->berhasil,
            'importGagal' => $import->gagal,
        ]);
    }

    public function importSimpanan(Request $request, MigrasiSimpananService $migrasi)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $import = new SimpananMigrasiImport($migrasi);
        Excel::import($import, $request->file('file'));

        return back()->with([
            'importBerhasil' => $import->berhasil,
            'importGagal' => $import->gagal,
        ]);
    }

    public function progresPinjaman()
    {
        $pinjaman = Pinjaman::with('anggota')->orderBy('id')->get();

        $kolom = [
            'No Karyawan', 'Nama', 'Nominal', 'Tenor',
            'Sudah Bayar', 'Sisa Cicilan', 'Sisa Total Bayar', 'Status',
        ];

        $rows = $pinjaman->map(function ($p) {
            $sudahBayar = $p->angsuran()->where('status', 'lunas')->count();

            return [
                $p->anggota->no_karyawan,
                $p->anggota->nama,
                (float) $p->nominal,
                $p->tenor_bulan,
                $sudahBayar,
                $p->sisaCicilanAktif(),
                $p->sisaTotalBayarAktif(),
                $p->status,
            ];
        })->all();

        return Excel::download(
            new ArrayExport([
                ['Progres Pinjaman Migrasi'],
                ['Periode: Semua Periode'],
                [],
                $kolom,
                ...$rows,
            ]),
            'progres-pinjaman-migrasi.xlsx'
        );
    }

    public function progresSimpanan()
    {
        $rekap = Simpanan::selectRaw('anggota_id, jenis, SUM(jumlah) as total')
            ->groupBy('anggota_id', 'jenis')
            ->get()
            ->groupBy('anggota_id');

        $anggota = Anggota::whereIn('id', $rekap->keys())->orderBy('no_karyawan')->get();

        $kolom = ['No Karyawan', 'Nama', 'Pokok', 'Wajib', 'Dana Sosial', 'Total'];

        $rows = $anggota->map(function ($a) use ($rekap) {
            $perJenis = ($rekap[$a->id] ?? collect())->keyBy('jenis');
            $pokok = (float) ($perJenis['pokok']->total ?? 0);
            $wajib = (float) ($perJenis['wajib']->total ?? 0);
            $sosial = (float) ($perJenis['dana_sosial']->total ?? 0);

            return [
                $a->no_karyawan, $a->nama,
                $pokok, $wajib, $sosial,
                $pokok + $wajib + $sosial,
            ];
        })->all();

        return Excel::download(
            new ArrayExport([
                ['Progres Simpanan Migrasi'],
                ['Periode: Semua Periode'],
                [],
                $kolom,
                ...$rows,
            ]),
            'progres-simpanan-migrasi.xlsx'
        );
    }
}

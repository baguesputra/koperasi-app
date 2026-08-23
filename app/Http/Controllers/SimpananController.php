<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Simpanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

class SimpananController extends Controller
{
    public function index(Request $request): Response
    {
        $daftarCabang = Config::get('cabang');
        $cabangAktif = $request->string('cabang');

        $query = Anggota::query()
            ->with(['simpanan' => fn ($q) => $q->latest('tanggal_input')])
            ->withSum(['simpanan as total_pokok_wajib' => fn ($q) => $q->whereIn('jenis', ['pokok', 'wajib'])], 'jumlah');

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where('nama', 'like', "%{$cari}%");
        }

        if ($cabangAktif->isNotEmpty()) {
            $query->where('cabang', $cabangAktif);
        }

        $anggota = $query->orderBy('nama')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($a) {
                $settlement = $a->resigned_settlement_json ?? [];

                return [
                    'id' => $a->id,
                    'nama' => $a->nama,
                    'no_anggota' => $a->no_anggota,
                    'cabang' => $a->cabang,
                    'status' => $a->status,
                    'total_simpanan' => (float) ($a->total_pokok_wajib ?? 0),
                    'alokasi_pelunasan_resign' => (float) ($settlement['tagihan_pelunasan'] ?? 0),
                    'alokasi_dari_pokok' => (float) ($settlement['alokasi_dari_pokok'] ?? 0),
                    'alokasi_dari_wajib' => (float) ($settlement['alokasi_dari_wajib'] ?? 0),
                    'tanggal_resign' => $a->tanggal_resign?->format('d M Y'),
                    'riwayat' => $a->simpanan->map(fn ($s) => [
                        'jenis' => $s->jenis,
                        'jumlah' => (float) $s->jumlah,
                        'bulan_periode' => $s->bulan_periode,
                        'tanggal_input' => $s->tanggal_input->format('d M Y'),
                    ]),
                ];
            });

        $totalDanaSosialTerkumpul = Simpanan::where('jenis', 'dana_sosial')->sum('jumlah');
        // Gross: akumulasi semua simpanan (untuk transparansi audit)
        $totalSimpananSeluruhAnggota = Simpanan::whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');
        // Outstanding: hanya anggota aktif (simpanan yang masih ditanggung koperasi)
        $totalSimpananOutstanding = (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])
            ->whereHas('anggota', fn ($q) => $q->where('status', 'aktif'))
            ->sum('jumlah');

        $totalSimpananTampil = $cabangAktif->isEmpty()
            ? $totalSimpananOutstanding
            : Simpanan::whereIn('jenis', ['pokok', 'wajib'])
                ->whereHas('anggota', fn ($q) => $q->where('status', 'aktif')->where('cabang', $cabangAktif))
                ->sum('jumlah');

        return Inertia::render('Simpanan/Index', [
            'anggota' => $anggota,
            'filters' => $request->only(['cari', 'cabang']),
            'cabangAktif' => $cabangAktif->value(),
            'daftarCabang' => $daftarCabang,
            'totalDanaSosialTerkumpul' => (float) $totalDanaSosialTerkumpul,
            'totalSimpananSeluruhAnggota' => (float) $totalSimpananSeluruhAnggota,
            'totalSimpananOutstanding' => $totalSimpananOutstanding,
            'totalSimpananTampil' => (float) $totalSimpananTampil,
        ]);
    }

    public function show(Anggota $anggota): Response
    {
        $riwayat = $anggota->simpanan()
            ->latest('tanggal_input')
            ->get()
            ->map(fn ($s) => [
                'jenis' => $s->jenis,
                'jumlah' => (float) $s->jumlah,
                'bulan_periode' => $s->bulan_periode,
                'tanggal_input' => $s->tanggal_input->format('d M Y'),
            ]);

        $totalSimpanan = $anggota->simpanan()->whereIn('jenis', ['pokok', 'wajib'])->sum('jumlah');

        $settlement = $anggota->resigned_settlement_json ?? [];
        $alokasiPelunasanResign = (float) ($settlement['tagihan_pelunasan'] ?? 0);
        $alokasiDariPokok = (float) ($settlement['alokasi_dari_pokok'] ?? 0);
        $alokasiDariWajib = (float) ($settlement['alokasi_dari_wajib'] ?? 0);
        $tanggalResign = $anggota->tanggal_resign?->format('d M Y');

        return Inertia::render('Simpanan/Show', [
            'anggota' => [
                'nama' => $anggota->nama,
                'no_anggota' => $anggota->no_anggota,
                'status' => $anggota->status,
            ],
            'riwayat' => $riwayat,
            'totalSimpanan' => (float) $totalSimpanan,
            'alokasiPelunasanResign' => $alokasiPelunasanResign,
            'alokasiDariPokok' => $alokasiDariPokok,
            'alokasiDariWajib' => $alokasiDariWajib,
            'tanggalResign' => $tanggalResign,
        ]);
    }
}

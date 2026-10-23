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
        $statusAktif = $request->string('status');

        $query = Anggota::query()
            ->with(['simpanan' => fn ($q) => $q->latest('tanggal_input')])
            ->withSum(['simpanan as total_pokok_wajib' => fn ($q) => $q->whereIn('jenis', ['pokok', 'wajib'])], 'jumlah');

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                    ->orWhere('no_karyawan', 'like', "%{$cari}%");
            });
        }

        if ($cabangAktif->isNotEmpty()) {
            $query->where('cabang', $cabangAktif->value());
        }

        if ($statusAktif->isNotEmpty()) {
            $query->where('status', $statusAktif->value());
        }

        $anggota = $query->orderBy('nama')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($a) {
                $settlement = $a->resigned_settlement_json ?? [];
                $pokok = (float) $a->simpanan->where('jenis', 'pokok')->sum('jumlah');
                $wajib = (float) $a->simpanan->where('jenis', 'wajib')->sum('jumlah');

                return [
                    'id' => $a->id,
                    'nama' => $a->nama,
                    'no_anggota' => $a->no_anggota,
                    'no_karyawan' => $a->no_karyawan,
                    'cabang' => $a->cabang,
                    'foto_url' => $a->foto_url,
                    'status' => $a->status,
                    'total_simpanan' => (float) ($a->total_pokok_wajib ?? 0),
                    'simpanan_pokok' => $pokok,
                    'simpanan_wajib' => $wajib,
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

        $agregatSimpanan = Simpanan::selectRaw("
            SUM(CASE WHEN jenis = 'pokok' THEN jumlah ELSE 0 END) as pokok,
            SUM(CASE WHEN jenis = 'wajib' THEN jumlah ELSE 0 END) as wajib,
            SUM(CASE WHEN jenis = 'dana_sosial' THEN jumlah ELSE 0 END) as sosial,
            SUM(CASE WHEN jenis IN ('pokok', 'wajib') THEN jumlah ELSE 0 END) as gross
        ")->first();

        $totalPokok = (float) ($agregatSimpanan->pokok ?? 0);
        $totalWajib = (float) ($agregatSimpanan->wajib ?? 0);
        $totalDanaSosialTerkumpul = (float) ($agregatSimpanan->sosial ?? 0);
        $totalSimpananSeluruhAnggota = (float) ($agregatSimpanan->gross ?? 0);

        // Outstanding: hanya anggota aktif (simpanan yang masih ditanggung koperasi)
        $totalSimpananOutstanding = (float) Simpanan::whereIn('jenis', ['pokok', 'wajib'])
            ->whereHas('anggota', fn ($q) => $q->where('status', 'aktif'))
            ->sum('jumlah');

        $totalSimpananTampil = $cabangAktif->isEmpty()
            ? $totalSimpananOutstanding
            : Simpanan::whereIn('jenis', ['pokok', 'wajib'])
                ->whereHas('anggota', fn ($q) => $q->where('status', 'aktif')->where('cabang', $cabangAktif->value()))
                ->sum('jumlah');

        $anggotaCounts = Anggota::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
            SUM(CASE WHEN status = 'resign' THEN 1 ELSE 0 END) as resign
        ")->first();

        return Inertia::render('Simpanan/Index', [
            'anggota' => $anggota,
            'filters' => $request->only(['cari', 'cabang', 'status']),
            'cabangAktif' => $cabangAktif->value(),
            'statusAktif' => $statusAktif->value(),
            'daftarCabang' => $daftarCabang,
            'totalPokok' => $totalPokok,
            'totalWajib' => $totalWajib,
            'totalDanaSosialTerkumpul' => $totalDanaSosialTerkumpul,
            'totalSimpananSeluruhAnggota' => $totalSimpananSeluruhAnggota,
            'totalSimpananOutstanding' => $totalSimpananOutstanding,
            'totalSimpananTampil' => (float) $totalSimpananTampil,
            'statistikAnggota' => [
                'total' => (int) ($anggotaCounts->total ?? 0),
                'aktif' => (int) ($anggotaCounts->aktif ?? 0),
                'resign' => (int) ($anggotaCounts->resign ?? 0),
            ],
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
                'no_karyawan' => $anggota->no_karyawan,
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

<?php

namespace App\Http\Controllers;

use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\Pinjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PinjamanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Pinjaman::with('anggota');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->whereHas('anggota', fn ($q) => $q->where('nama', 'like', "%{$cari}%"));
        }

        $pinjaman = $query->latest('tanggal_pengajuan')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($p) {
                $pelunasanResign = $this->hitungPelunasanResign($p);

                return [
                    'id' => $p->id,
                    'nama' => $p->anggota->nama,
                    'no_anggota' => $p->anggota->no_anggota,
                    'anggota_status' => $p->anggota->status,
                    'nominal' => (float) $p->nominal,
                    'tenor_bulan' => $p->tenor_bulan,
                    'status' => $p->status,
                    'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
                    'pelunasan_resign_total' => $pelunasanResign['total'],
                    'tanggal_pelunasan_resign' => $pelunasanResign['tanggal'],
                    'is_resign' => $p->anggota->status === 'resign',
                ];
            });

        return Inertia::render('Pinjaman/Index', [
            'pinjaman' => $pinjaman,
            'filters' => $request->only(['cari', 'status']),
            'statistik' => [
                'total' => Pinjaman::count(),
                'diajukan' => Pinjaman::where('status', 'diajukan')->count(),
                'approved_bendahara' => Pinjaman::where('status', 'approved_bendahara')->count(),
                'aktif' => Pinjaman::where('status', 'aktif')->count(),
                'lunas' => Pinjaman::where('status', 'lunas')->count(),
                'ditolak' => Pinjaman::where('status', 'ditolak')->count(),
            ],
        ]);
    }

    public function show(Pinjaman $pinjaman): Response
    {
        $pinjaman->load(['anggota', 'angsuran', 'pengajuanPercepatan']);

        $angsuranList = $pinjaman->angsuran()
            ->orderBy('cicilan_ke')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'cicilan_ke' => $a->cicilan_ke,
                'total_bayar' => (float) $a->total_bayar,
                'status' => $a->status,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('d M Y'),
                'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
            ]);

        $pelunasanResign = $this->hitungPelunasanResign($pinjaman);
        $jurnalPelunasan = $this->ambilJurnalPelunasanResign($pinjaman);

        return Inertia::render('Pinjaman/Show', [
            'pinjaman' => [
                'id' => $pinjaman->id,
                'anggota_id' => $pinjaman->anggota_id,
                'nama' => $pinjaman->anggota->nama,
                'no_anggota' => $pinjaman->anggota->no_anggota,
                'anggota_status' => $pinjaman->anggota->status,
                'nominal' => (float) $pinjaman->nominal,
                'tenor_bulan' => $pinjaman->tenor_bulan,
                'status' => $pinjaman->status,
                'tanggal_pengajuan' => $pinjaman->tanggal_pengajuan->format('d M Y'),
                'tanggal_cair' => $pinjaman->tanggal_cair?->format('d M Y'),
                'keperluan' => $pinjaman->keperluan,
            ],
            'angsuran' => $angsuranList,
            'pelunasan_resign' => $pelunasanResign,
            'jurnal_pelunasan' => $jurnalPelunasan,
        ]);
    }

    private function hitungPelunasanResign(Pinjaman $p): array
    {
        $angsuranIds = Angsuran::where('pinjaman_id', $p->id)->pluck('id');

        if ($angsuranIds->isEmpty()) {
            return ['total' => 0.0, 'tanggal' => null];
        }

        $row = JurnalKas::where('kategori', 'pelunasan_resign_pinjaman')
            ->whereIn('referensi_id', $angsuranIds)
            ->selectRaw('SUM(jumlah) as total, MAX(tanggal) as tanggal')
            ->first();

        return [
            'total' => (float) ($row->total ?? 0),
            'tanggal' => $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d M Y') : null,
        ];
    }

    private function ambilJurnalPelunasanResign(Pinjaman $p): array
    {
        $angsuranIds = Angsuran::where('pinjaman_id', $p->id)->pluck('id');

        if ($angsuranIds->isEmpty()) {
            return [];
        }

        return JurnalKas::where('kategori', 'pelunasan_resign_pinjaman')
            ->whereIn('referensi_id', $angsuranIds)
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($j) => [
                'id' => $j->id,
                'jumlah' => (float) $j->jumlah,
                'tanggal' => $j->tanggal->format('d M Y'),
                'keterangan' => $j->keterangan,
                'sub_judul' => $j->sub_judul,
            ])
            ->all();
    }
}

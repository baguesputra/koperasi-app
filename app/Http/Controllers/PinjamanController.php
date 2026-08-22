<?php

namespace App\Http\Controllers;

use App\Helpers\TerbilangHelper;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PerhitunganBungaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PinjamanController extends Controller
{
    public function __construct(
        private PerhitunganBungaService $bunga,
    ) {}
    public function index(Request $request): Response
    {
        $query = Pinjaman::with(['anggota', 'angsuran']);

        $cabangAktif = $request->string('cabang');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->whereHas('anggota', fn ($q) => $q->where('nama', 'like', "%{$cari}%"));
        }

        if ($cabangAktif->isNotEmpty()) {
            $query->whereHas('anggota', fn ($q) => $q->where('cabang', $cabangAktif));
        }

        $pinjaman = $query->latest('tanggal_pengajuan')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($p) {
                $pelunasanResign = $this->hitungPelunasanResign($p);
                $jurnalPelunasan = $this->ambilJurnalPelunasanResign($p);

                return [
                    'id' => $p->id,
                    'nama' => $p->anggota->nama,
                    'no_anggota' => $p->anggota->no_anggota,
                    'cabang' => $p->anggota->cabang,
                    'anggota_status' => $p->anggota->status,
                    'nominal' => (float) $p->nominal,
                    'tenor_bulan' => $p->tenor_bulan,
                    'status' => $p->status,
                    'tanggal_pengajuan' => $p->tanggal_pengajuan->format('d M Y'),
                    'tanggal_cair' => $p->tanggal_cair?->format('d M Y'),
                    'keperluan' => $p->keperluan,
                    'pelunasan_resign_total' => $pelunasanResign['total'],
                    'tanggal_pelunasan_resign' => $pelunasanResign['tanggal'],
                    'is_resign' => $p->anggota->status === 'resign',
                    'angsuran' => $p->angsuran
                        ->sortBy('cicilan_ke')
                        ->map(fn ($a) => [
                            'id' => $a->id,
                            'cicilan_ke' => $a->cicilan_ke,
                            'total_bayar' => (float) $a->total_bayar,
                            'status' => $a->status,
                            'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('d M Y'),
                            'tanggal_konfirmasi_bayar' => $a->tanggal_konfirmasi_bayar?->format('d M Y'),
                        ])
                        ->values(),
                    'pelunasan_resign' => $pelunasanResign,
                    'jurnal_pelunasan' => $jurnalPelunasan,
                ];
            });

        $daftarCabang = Anggota::query()->whereNotNull('cabang')->distinct()->orderBy('cabang')->pluck('cabang');

        return Inertia::render('Pinjaman/Index', [
            'pinjaman' => $pinjaman,
            'filters' => $request->only(['cari', 'status', 'cabang']),
            'cabangAktif' => $cabangAktif->value(),
            'daftarCabang' => $daftarCabang,
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

    public function cetakBukti(Pinjaman $pinjaman): Response
    {
        abort_unless($pinjaman->status === 'aktif', 403, 'Bukti peminjaman hanya tersedia untuk pinjaman dengan status Aktif.');

        $pinjaman->load(['anggota', 'angsuran']);

        $angsuranList = $pinjaman->angsuran()
            ->orderBy('cicilan_ke')
            ->get()
            ->map(fn ($a) => [
                'cicilan_ke' => $a->cicilan_ke,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('d M Y'),
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'status' => $a->status,
            ]);

        $totalPokok = $angsuranList->sum('nominal_pokok');
        $totalBunga = $angsuranList->sum('nominal_bunga');
        $totalAngsuran = $angsuranList->sum('total_bayar');

        return Inertia::render('Pinjaman/CetakBukti', [
            'pinjaman' => [
                'id' => $pinjaman->id,
                'nominal' => (float) $pinjaman->nominal,
                'terbilang' => TerbilangHelper::angkaKeTerbilang($pinjaman->nominal),
                'tenor_bulan' => $pinjaman->tenor_bulan,
                'persentase_bunga' => (float) $pinjaman->persentase_bunga,
                'keperluan' => $pinjaman->keperluan,
                'tanggal_pengajuan' => $pinjaman->tanggal_pengajuan->format('d M Y'),
                'tanggal_cair' => $pinjaman->tanggal_cair?->format('d M Y'),
                'rekening' => [
                    'bank' => $pinjaman->snapshot_bank,
                    'no_rekening' => $pinjaman->snapshot_no_rekening,
                    'atas_nama' => $pinjaman->snapshot_atas_nama,
                ],
                'anggota' => [
                    'id' => $pinjaman->anggota->id,
                    'no_anggota' => $pinjaman->anggota->no_anggota,
                    'no_karyawan' => $pinjaman->anggota->no_karyawan,
                    'nama' => $pinjaman->anggota->nama,
                    'cabang' => $pinjaman->anggota->cabang,
                    'unit_bisnis' => $pinjaman->anggota->unit_bisnis,
                    'jabatan' => $pinjaman->anggota->jabatan,
                ],
            ],
            'angsuran' => $angsuranList,
            'totals' => [
                'pokok' => $totalPokok,
                'bunga' => $totalBunga,
                'angsuran' => $totalAngsuran,
            ],
        ]);
    }
}

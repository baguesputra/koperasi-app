<?php

namespace App\Http\Controllers;

use App\Models\RekeningTabungan;
use App\Models\SettingBungaTabungan;
use App\Models\SettingTabungan;
use App\Models\TabunganBungaPeriode;
use App\Services\Tabungan\PerhitunganBungaTabunganService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

class TabunganController extends Controller
{
    public function __construct(private PerhitunganBungaTabunganService $bungaService) {}

    public function index(Request $request): Response
    {
        $daftarCabang = array_values(Config::get('cabang', []));
        $cari = trim((string) $request->input('cari', ''));
        $cabangAktif = (string) $request->input('cabang', '');
        $statusAktif = (string) $request->input('status', '');

        $query = RekeningTabungan::query()
            ->with(['anggota:id,nama,no_anggota,no_karyawan,cabang,foto_url,status'])
            ->orderByRaw("CASE status WHEN 'aktif' THEN 1 WHEN 'diajukan' THEN 2 WHEN 'ditutup' THEN 3 ELSE 4 END")
            ->orderByDesc('id');

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('no_rekening', 'like', "%{$cari}%")
                    ->orWhereHas('anggota', function ($qa) use ($cari) {
                        $qa->where('nama', 'like', "%{$cari}%")
                            ->orWhere('no_karyawan', 'like', "%{$cari}%");
                    });
            });
        }

        if ($cabangAktif !== '') {
            $query->whereHas('anggota', fn ($qa) => $qa->where('cabang', $cabangAktif));
        }

        if (in_array($statusAktif, ['aktif', 'diajukan', 'ditolak', 'ditutup'], true)) {
            $query->where('status', $statusAktif);
        }

        $rekening = $query->paginate(15)->withQueryString()->through(fn ($r) => [
            'id' => $r->id,
            'no_rekening' => $r->no_rekening,
            'status' => $r->status,
            'saldo' => (float) $r->saldo,
            'suku_bunga_saat_buka' => (float) $r->suku_bunga_saat_buka,
            'tanggal_pengajuan' => $r->tanggal_pengajuan?->format('d M Y'),
            'tanggal_buka' => $r->tanggal_buka?->format('d M Y'),
            'tanggal_tutup' => $r->tanggal_tutup?->format('d M Y'),
            'anggota' => $r->anggota ? [
                'id' => $r->anggota->id,
                'nama' => $r->anggota->nama,
                'no_karyawan' => $r->anggota->no_karyawan,
                'no_anggota' => $r->anggota->no_anggota,
                'cabang' => $r->anggota->cabang,
                'foto_url' => $r->anggota->foto_url,
                'status' => $r->anggota->status,
            ] : null,
        ]);

        $agregat = RekeningTabungan::selectRaw("
            COALESCE(SUM(CASE WHEN status = 'aktif' THEN saldo ELSE 0 END), 0) as total_saldo_aktif,
            SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as jumlah_aktif,
            SUM(CASE WHEN status = 'diajukan' THEN 1 ELSE 0 END) as jumlah_diajukan
        ")->first();

        $tahunIni = (string) now()->year;
        $totalBungaTahunIni = (float) TabunganBungaPeriode::where('periode', 'like', "{$tahunIni}-%")->sum('bunga_bersih');

        $bisaKelola = $request->user()->can('tabungan.kelola');
        $tabAktif = (string) $request->input('tab', 'rekening');
        if (! $bisaKelola || ! in_array($tabAktif, ['rekening', 'bunga'], true)) {
            $tabAktif = 'rekening';
        }

        $periodeBunga = (string) $request->input('periode', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $periodeBunga)) {
            $periodeBunga = now()->format('Y-m');
        }

        $sukuBungaAktif = SettingBungaTabungan::persentaseAktif();

        $rekeningAktifOptions = collect();
        $settingData = null;
        $previewBunga = null;

        if ($bisaKelola) {
            $rekeningAktifOptions = RekeningTabungan::with('anggota:id,nama,no_karyawan,cabang')
                ->where('status', 'aktif')
                ->orderBy('no_rekening')
                ->get(['id', 'anggota_id', 'no_rekening', 'saldo'])
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'no_rekening' => $r->no_rekening,
                    'saldo' => (float) $r->saldo,
                    'nama' => $r->anggota?->nama ?? '-',
                    'no_karyawan' => $r->anggota?->no_karyawan ?? '-',
                    'cabang' => $r->anggota?->cabang ?? '-',
                ]);

            $settingModel = SettingTabungan::current();
            $settingData = [
                'min_setor' => (float) $settingModel->min_setor,
                'saldo_minimal' => (float) $settingModel->saldo_minimal,
                'suku_bunga_aktif' => $sukuBungaAktif,
            ];

            if ($tabAktif === 'bunga') {
                $previewBunga = $this->bungaService->previewPostingPeriode($periodeBunga);
            }
        }

        return Inertia::render('Tabungan/Index', [
            'rekening' => $rekening,
            'filters' => [
                'cari' => $cari,
                'cabang' => $cabangAktif,
                'status' => $statusAktif,
            ],
            'daftarCabang' => $daftarCabang,
            'statistik' => [
                'total_saldo_aktif' => (float) ($agregat->total_saldo_aktif ?? 0),
                'jumlah_aktif' => (int) ($agregat->jumlah_aktif ?? 0),
                'jumlah_diajukan' => (int) ($agregat->jumlah_diajukan ?? 0),
                'total_bunga_tahun_ini' => $totalBungaTahunIni,
                'suku_bunga_aktif' => $sukuBungaAktif,
            ],
            'bisaKelola' => $bisaKelola,
            'tabAktif' => $tabAktif,
            'periodeBunga' => $periodeBunga,
            'previewBunga' => $previewBunga,
            'rekeningAktifOptions' => $rekeningAktifOptions,
            'setting' => $settingData,
        ]);
    }

    public function show(Request $request, RekeningTabungan $rekening): Response
    {
        $rekening->load(['anggota:id,nama,no_anggota,no_karyawan,cabang,unit_bisnis,jabatan,foto_url,status', 'diprosesOleh:id,name']);

        $estimasiBunga = $rekening->status === 'aktif'
            ? $this->bungaService->estimasiBulanBerjalan($rekening)
            : null;

        $jenisFilter = (string) $request->input('jenis', '');
        $trxQuery = $rekening->transaksi()
            ->with(['createdBy:id,name', 'koreksiDari:id,no_bukti'])
            ->latest('tanggal')
            ->latest('id');

        if (in_array($jenisFilter, ['setor', 'tarik', 'bunga', 'koreksi_masuk', 'koreksi_keluar', 'tutup_rekening'], true)) {
            $trxQuery->where('jenis', $jenisFilter);
        }

        $transaksi = $trxQuery->paginate(20)->withQueryString()->through(fn ($t) => [
            'id' => $t->id,
            'no_bukti' => $t->no_bukti,
            'jenis' => $t->jenis,
            'kanal' => $t->kanal,
            'is_kredit' => $t->isKredit(),
            'nominal' => (float) $t->nominal,
            'saldo_sebelum' => (float) $t->saldo_sebelum,
            'saldo_sesudah' => (float) $t->saldo_sesudah,
            'suku_bunga_snapshot' => $t->suku_bunga_snapshot !== null ? (float) $t->suku_bunga_snapshot : null,
            'tanggal' => $t->tanggal?->format('d M Y'),
            'keterangan' => $t->keterangan,
            'created_by_name' => $t->createdBy?->name,
            'dibatalkan_pada' => $t->dibatalkan_pada?->format('d M Y H:i'),
            'alasan_batal' => $t->alasan_batal,
            'koreksi_dari_no_bukti' => $t->koreksiDari?->no_bukti,
            'bisa_dibatalkan' => $rekening->status === 'aktif'
                && $t->dibatalkan_pada === null
                && $t->koreksi_dari_id === null
                && in_array($t->jenis, ['setor', 'tarik'], true),
            'bukti_url' => $t->bukti_path ? route('tabungan.bukti.transaksi', $t->id) : null,
        ]);

        $riwayatBunga = $rekening->bungaPeriode()
            ->with('dipostingOleh:id,name')
            ->orderByDesc('periode')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'periode' => $b->periode,
                'metode_hitung_snapshot' => $b->metode_hitung_snapshot,
                'suku_bunga_snapshot' => (float) $b->suku_bunga_snapshot,
                'rincian_tarif' => $b->rincian_tarif_json ?? [],
                'saldo_dasar' => (float) $b->saldo_dasar,
                'jumlah_hari' => (int) $b->jumlah_hari,
                'nominal_bunga' => (float) $b->nominal_bunga,
                'bunga_bersih' => (float) $b->bunga_bersih,
                'diposting_oleh' => $b->dipostingOleh?->name ?? 'Otomatis (Sistem)',
                'diposting_pada' => $b->diposting_pada?->format('d M Y H:i'),
            ]);

        $setting = SettingTabungan::current();

        return Inertia::render('Tabungan/Show', [
            'rekening' => [
                'id' => $rekening->id,
                'no_rekening' => $rekening->no_rekening,
                'status' => $rekening->status,
                'saldo' => (float) $rekening->saldo,
                'suku_bunga_saat_buka' => (float) $rekening->suku_bunga_saat_buka,
                'versi_syarat' => $rekening->versi_syarat,
                'tanggal_pengajuan' => $rekening->tanggal_pengajuan?->format('d M Y'),
                'tanggal_buka' => $rekening->tanggal_buka?->format('d M Y'),
                'tanggal_tutup' => $rekening->tanggal_tutup?->format('d M Y'),
                'diproses_oleh' => $rekening->diprosesOleh?->name,
                'catatan_pengurus' => $rekening->catatan_pengurus,
                'anggota' => $rekening->anggota ? [
                    'id' => $rekening->anggota->id,
                    'nama' => $rekening->anggota->nama,
                    'no_karyawan' => $rekening->anggota->no_karyawan,
                    'no_anggota' => $rekening->anggota->no_anggota,
                    'cabang' => $rekening->anggota->cabang,
                    'unit_bisnis' => $rekening->anggota->unit_bisnis,
                    'jabatan' => $rekening->anggota->jabatan,
                    'foto_url' => $rekening->anggota->foto_url,
                    'status' => $rekening->anggota->status,
                ] : null,
            ],
            'sukuBungaAktif' => SettingBungaTabungan::persentaseAktif(),
            'minSetor' => (float) $setting->min_setor,
            'estimasiBunga' => $estimasiBunga,
            'transaksi' => $transaksi,
            'riwayatBunga' => $riwayatBunga,
            'filters' => ['jenis' => $jenisFilter],
        ]);
    }
}

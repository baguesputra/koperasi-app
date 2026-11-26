<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Resources\PinjamanResource;
use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\RekeningTabungan;
use App\Models\SettingBungaTabungan;
use App\Models\SettingTabungan;
use App\Models\Simpanan;
use App\Services\Tabungan\PerhitunganBungaTabunganService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListController extends Controller
{
    private const STATUS_SAH = ['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua', 'aktif', 'lunas', 'ditolak', 'dihapusbukukan'];

    /**
     * Daftar semua pinjaman + rekap per status mengikuti filter cari.
     * Mirror mock /pengurus/list-pinjaman + kartu statistik web Pinjaman/Index.
     *
     * @response 200 {"data": [{"id": 1}], "meta": {"current_page": 1}, "rekap": [{"status": "aktif"}]}
     */
    public function pinjaman(Request $request): JsonResponse
    {
        if (! $request->user()->can('pinjaman.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke data pinjaman.',
            ], 403));
        }

        $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = (string) $request->input('status', '');
        if ($status !== '' && $status !== 'semua' && ! in_array($status, self::STATUS_SAH, true)) {
            return response()->json(['message' => 'Status tidak dikenal.'], 422);
        }

        $cari = trim((string) $request->input('cari', ''));

        $dasar = Pinjaman::with(['anggota', 'pengaju', 'dicairkanOleh:id,name']);
        $this->terapkanCari($dasar, $cari);

        $rekap = (clone $dasar)
            ->selectRaw('status, COUNT(*) as jumlah, COALESCE(SUM(nominal), 0) as total_nominal')
            ->groupBy('status')
            ->pluck('total_nominal', 'status');

        // Rekap mengikuti hasil pencarian (tanpa filter status) ala kartu statistik web.
        $rekapJumlah = (clone $dasar)
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $query = clone $dasar;
        if ($status !== '' && $status !== 'semua') {
            $query->where('status', $status);
        }

        $halaman = $query->latest('tanggal_pengajuan')->latest('id')
            ->paginate($request->input('per_page', 15), ['*'], 'page', $request->input('page', 1));

        return response()->json([
            'data' => PinjamanResource::collection($halaman->getCollection())->resolve(),
            'meta' => [
                'current_page' => $halaman->currentPage(),
                'last_page' => $halaman->lastPage(),
                'per_page' => $halaman->perPage(),
                'total' => $halaman->total(),
            ],
            'rekap' => collect(self::STATUS_SAH)->map(fn ($s) => [
                'status' => $s,
                'jumlah' => (int) ($rekapJumlah[$s] ?? 0),
                'total_nominal' => (float) ($rekap[$s] ?? 0),
            ])->all(),
        ]);
    }

    /**
     * Daftar simpanan per anggota (pokok/wajib/sosial + setoran terakhir).
     * Mirror mock /pengurus/list-simpanan + web Simpanan/Index.
     *
     * @response 200 {"data": [{"anggota_id": 1}], "meta": {"current_page": 1}}
     */
    public function simpanan(Request $request): JsonResponse
    {
        if (! $request->user()->can('simpanan.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke data simpanan.',
            ], 403));
        }

        $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Anggota::query()->with(['simpanan' => fn ($q) => $q->latest('tanggal_input')]);

        $cari = trim((string) $request->input('cari', ''));
        if ($cari !== '') {
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('no_karyawan', 'like', "%{$cari}%")
                ->orWhere('no_anggota', 'like', "%{$cari}%"));
        }

        $query->withSum(['simpanan as agregat_pokok' => fn ($q) => $q->where('jenis', 'pokok')], 'jumlah')
            ->withSum(['simpanan as agregat_wajib' => fn ($q) => $q->where('jenis', 'wajib')], 'jumlah')
            ->withSum(['simpanan as agregat_sosial' => fn ($q) => $q->where('jenis', 'dana_sosial')], 'jumlah')
            ->orderByDesc('agregat_pokok')
            ->orderByDesc('agregat_wajib');

        $halaman = $query->paginate($request->input('per_page', 15), ['*'], 'page', $request->input('page', 1));

        $data = $halaman->getCollection()->map(fn ($a) => $this->barisSimpanan($a))->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $halaman->currentPage(),
                'last_page' => $halaman->lastPage(),
                'per_page' => $halaman->perPage(),
                'total' => $halaman->total(),
            ],
        ]);
    }

    /**
     * Detail simpanan satu anggota (ringkas agregat + riwayat).
     * Mirror mock /pengurus/list-simpanan/{id}.
     *
     * @response 200 {"anggota": {"id": 1}, "total": 100000, "data": []}
     */
    public function simpananDetail(Request $request, Anggota $anggota): JsonResponse
    {
        if (! $request->user()->can('simpanan.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke data simpanan.',
            ], 403));
        }

        $anggota->load(['simpanan' => fn ($q) => $q->latest('tanggal_input')]);

        $baris = $this->barisSimpanan($anggota);

        return response()->json([
            'anggota' => $baris['anggota'],
            'pokok' => $baris['simpanan_pokok'],
            'wajib' => $baris['simpanan_wajib'],
            'dana_sosial' => $baris['dana_sosial'],
            'total' => $baris['total_simpanan'],
            'data' => $anggota->simpanan->map(fn ($s) => [
                'id' => $s->id,
                'anggota_id' => $s->anggota_id,
                'jenis' => $s->jenis,
                'jumlah' => (float) $s->jumlah,
                'bulan_periode' => $s->bulan_periode,
                'tanggal_input' => $s->tanggal_input?->format('Y-m-d'),
            ])->values(),
        ]);
    }

    /**
     * Daftar seluruh anggota beserta info kepegawaian & rekap status.
     */
    public function anggota(Request $request): JsonResponse
    {
        if (! $request->user()->can('anggota.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke data anggota.',
            ], 403));
        }

        $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:semua,aktif,nonaktif,resign,resign_menunggu'],
            'cabang' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $cari = trim((string) $request->input('cari', ''));
        $status = (string) $request->input('status', '');
        $cabang = trim((string) $request->input('cabang', ''));

        $query = Anggota::with(['user:id,email', 'perusahaan:id,nama'])->orderBy('nama');

        if ($cari !== '') {
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('no_karyawan', 'like', "%{$cari}%")
                ->orWhere('no_anggota', 'like', "%{$cari}%"));
        }
        if ($cabang !== '') {
            $query->where('cabang', $cabang);
        }
        if ($status !== '' && $status !== 'semua') {
            if ($status === 'resign') {
                $query->whereIn('status', ['resign', 'resign_menunggu']);
            } else {
                $query->where('status', $status);
            }
        }

        $halaman = $query->paginate($request->integer('per_page', 15), ['*'], 'page', $request->integer('page', 1));

        $statistik = Anggota::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) as nonaktif,
                SUM(CASE WHEN status IN ('resign', 'resign_menunggu') THEN 1 ELSE 0 END) as resign
            ")
            ->first();

        $data = $halaman->getCollection()->map(function (Anggota $a) {
            $tgl = $a->tanggal_jadi_anggota ?? $a->tanggal_mulai_kerja;

            return [
                'id' => $a->id,
                'no_anggota' => $a->no_anggota,
                'no_karyawan' => $a->no_karyawan,
                'nama' => $a->nama,
                'jabatan' => $a->jabatan,
                'cabang' => $a->cabang,
                'perusahaan' => $a->getRelationValue('perusahaan')?->nama,
                'unit_bisnis' => $a->unit_bisnis,
                'department' => $a->department,
                'divisi' => $a->divisi,
                'no_hp' => $a->no_hp,
                'alamat' => $a->alamat,
                'email' => $a->getRelationValue('user')?->email,
                'status' => $a->status,
                'foto_url' => $a->foto_url,
                'tanggal_mulai_kerja' => $a->tanggal_mulai_kerja?->format('Y-m-d'),
                'tanggal_jadi_anggota' => $a->tanggal_jadi_anggota?->format('Y-m-d'),
                'lama_keanggotaan_tahun' => $tgl ? round($tgl->diffInDays(now()) / 365, 1) : 0.0,
                'lama_keanggotaan_label' => Anggota::formatLamaKeanggotaan($tgl),
                'limit_custom' => $a->limit_custom !== null ? (float) $a->limit_custom : null,
                'limit_custom_keterangan' => $a->limit_custom_keterangan,
                'status_pencairan_resign' => $a->status_pencairan_resign,
                'tanggal_pencairan_resign' => $a->tanggal_pencairan_resign?->format('Y-m-d'),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $halaman->currentPage(),
                'last_page' => $halaman->lastPage(),
                'per_page' => $halaman->perPage(),
                'total' => $halaman->total(),
            ],
            'rekap' => [
                'total' => (int) ($statistik->total ?? 0),
                'aktif' => (int) ($statistik->aktif ?? 0),
                'nonaktif' => (int) ($statistik->nonaktif ?? 0),
                'resign' => (int) ($statistik->resign ?? 0),
            ],
        ]);
    }

    /**
     * Daftar rekening tabungan anggota beserta statistik ringkas.
     */
    public function tabungan(Request $request): JsonResponse
    {
        if (! $request->user()->can('tabungan.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke data tabungan.',
            ], 403));
        }

        $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:semua,aktif,diajukan,approved_admin,approved_bendahara,ditolak,ditutup'],
            'cabang' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $cari = trim((string) $request->input('cari', ''));
        $status = (string) $request->input('status', '');
        $cabang = trim((string) $request->input('cabang', ''));

        $query = RekeningTabungan::with(['anggota:id,nama,no_anggota,no_karyawan,cabang,foto_url,status'])
            ->orderByRaw("CASE status WHEN 'aktif' THEN 1 WHEN 'diajukan' THEN 2 WHEN 'approved_admin' THEN 3 WHEN 'approved_bendahara' THEN 4 WHEN 'ditutup' THEN 5 ELSE 6 END")
            ->orderByDesc('id');

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('no_rekening', 'like', "%{$cari}%")
                    ->orWhereHas('anggota', fn ($qa) => $qa
                        ->where('nama', 'like', "%{$cari}%")
                        ->orWhere('no_karyawan', 'like', "%{$cari}%")
                        ->orWhere('no_anggota', 'like', "%{$cari}%"));
            });
        }

        if ($cabang !== '') {
            $query->whereHas('anggota', fn ($qa) => $qa->where('cabang', $cabang));
        }

        if ($status !== '' && $status !== 'semua') {
            $query->where('status', $status);
        }

        $halaman = $query->paginate($request->integer('per_page', 15), ['*'], 'page', $request->integer('page', 1));

        $agregat = RekeningTabungan::selectRaw("
            COALESCE(SUM(CASE WHEN status = 'aktif' THEN saldo ELSE 0 END), 0) as total_saldo_aktif,
            SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as jumlah_aktif,
            SUM(CASE WHEN status IN ('diajukan','approved_admin','approved_bendahara') THEN 1 ELSE 0 END) as jumlah_diajukan
        ")->first();

        $setting = SettingTabungan::current();

        return response()->json([
            'data' => $halaman->getCollection()->map(fn ($r) => [
                'id' => $r->id,
                'no_rekening' => $r->no_rekening,
                'status' => $r->status,
                'saldo' => (float) $r->saldo,
                'suku_bunga_saat_buka' => (float) $r->suku_bunga_saat_buka,
                'tanggal_pengajuan' => $r->tanggal_pengajuan?->format('Y-m-d'),
                'tanggal_buka' => $r->tanggal_buka?->format('Y-m-d'),
                'tanggal_tutup' => $r->tanggal_tutup?->format('Y-m-d'),
                'anggota' => $r->anggota ? [
                    'id' => $r->anggota->id,
                    'nama' => $r->anggota->nama,
                    'no_karyawan' => $r->anggota->no_karyawan,
                    'no_anggota' => $r->anggota->no_anggota,
                    'cabang' => $r->anggota->cabang,
                    'foto_url' => $r->anggota->foto_url,
                    'status' => $r->anggota->status,
                ] : null,
            ])->values(),
            'meta' => [
                'current_page' => $halaman->currentPage(),
                'last_page' => $halaman->lastPage(),
                'per_page' => $halaman->perPage(),
                'total' => $halaman->total(),
            ],
            'statistik' => [
                'total_saldo_aktif' => (float) ($agregat->total_saldo_aktif ?? 0),
                'jumlah_aktif' => (int) ($agregat->jumlah_aktif ?? 0),
                'jumlah_diajukan' => (int) ($agregat->jumlah_diajukan ?? 0),
                'suku_bunga_aktif' => SettingBungaTabungan::persentaseAktif(),
                'min_setor' => (float) $setting->min_setor,
            ],
        ]);
    }

    /**
     * Detail rekening tabungan + mutasi & bunga periode.
     */
    public function tabunganDetail(Request $request, RekeningTabungan $rekening): JsonResponse
    {
        if (! $request->user()->can('tabungan.lihat')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke data tabungan.',
            ], 403));
        }

        $rekening->load(['anggota:id,nama,no_anggota,no_karyawan,cabang,unit_bisnis,jabatan,foto_url,status', 'diprosesOleh:id,name']);

        $estimasiBunga = $rekening->status === 'aktif'
            ? app(PerhitunganBungaTabunganService::class)->estimasiBulanBerjalan($rekening)
            : null;

        $transaksi = $rekening->transaksi()
            ->with(['createdBy:id,name', 'koreksiDari:id,no_bukti'])
            ->latest('tanggal')
            ->latest('id')
            ->take(50)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'no_bukti' => $t->no_bukti,
                'jenis' => $t->jenis,
                'kanal' => $t->kanal,
                'is_kredit' => $t->isKredit(),
                'nominal' => (float) $t->nominal,
                'saldo_sebelum' => (float) $t->saldo_sebelum,
                'saldo_sesudah' => (float) $t->saldo_sesudah,
                'suku_bunga_snapshot' => $t->suku_bunga_snapshot !== null ? (float) $t->suku_bunga_snapshot : null,
                'tanggal' => $t->tanggal?->format('Y-m-d'),
                'keterangan' => $t->keterangan,
                'created_by_name' => $t->createdBy?->name,
                'dibatalkan_pada' => $t->dibatalkan_pada?->format('Y-m-d H:i'),
                'alasan_batal' => $t->alasan_batal,
                'koreksi_dari_no_bukti' => $t->koreksiDari?->no_bukti,
                'bisa_dibatalkan' => $rekening->status === 'aktif'
                    && $t->dibatalkan_pada === null
                    && $t->koreksi_dari_id === null
                    && in_array($t->jenis, ['setor', 'tarik'], true),
                'bukti_url' => $t->bukti_path ? route('tabungan.bukti.transaksi', $t->id) : null,
            ])->values();

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
                'diposting_pada' => $b->diposting_pada?->format('Y-m-d H:i'),
            ])->values();

        return response()->json([
            'rekening' => [
                'id' => $rekening->id,
                'no_rekening' => $rekening->no_rekening,
                'status' => $rekening->status,
                'saldo' => (float) $rekening->saldo,
                'suku_bunga_saat_buka' => (float) $rekening->suku_bunga_saat_buka,
                'tanggal_pengajuan' => $rekening->tanggal_pengajuan?->format('Y-m-d'),
                'tanggal_buka' => $rekening->tanggal_buka?->format('Y-m-d'),
                'tanggal_tutup' => $rekening->tanggal_tutup?->format('Y-m-d'),
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
            'suku_bunga_aktif' => SettingBungaTabungan::persentaseAktif(),
            'min_setor' => (float) SettingTabungan::current()->min_setor,
            'estimasi_bunga' => $estimasiBunga,
            'transaksi' => $transaksi,
            'riwayat_bunga' => $riwayatBunga,
        ]);
    }

    private function barisSimpanan(Anggota $a): array
    {
        $pokok = (float) ($a->agregat_pokok
            ?? $a->simpanan->where('jenis', 'pokok')->sum('jumlah'));
        $wajib = (float) ($a->agregat_wajib
            ?? $a->simpanan->where('jenis', 'wajib')->sum('jumlah'));
        $sosial = (float) ($a->agregat_sosial
            ?? $a->simpanan->where('jenis', 'dana_sosial')->sum('jumlah'));

        return [
            'anggota_id' => $a->id,
            'anggota' => [
                'id' => $a->id,
                'no_anggota' => $a->no_anggota,
                'no_karyawan' => $a->no_karyawan,
                'nama' => $a->nama,
                'jabatan' => $a->jabatan,
                'cabang' => $a->cabang,
                'unit_bisnis' => $a->unit_bisnis,
                'department' => $a->department,
                'divisi' => $a->divisi,
                'no_hp' => $a->no_hp,
                'status' => $a->status,
                'foto_url' => $a->foto_url,
            ],
            'simpanan_pokok' => $pokok,
            'simpanan_wajib' => $wajib,
            'dana_sosial' => $sosial,
            'total_simpanan' => $pokok + $wajib + $sosial,
            'setoran_terakhir' => $a->simpanan->first()?->tanggal_input?->format('Y-m-d'),
        ];
    }

    private function terapkanCari($query, string $cari): void
    {
        if ($cari === '') {
            return;
        }

        $query->where(function ($q) use ($cari) {
            $q->whereHas('anggota', fn ($qq) => $qq
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('no_karyawan', 'like', "%{$cari}%")
                ->orWhere('no_anggota', 'like', "%{$cari}%"))
                ->orWhere('nomor_dokumen', 'like', "%{$cari}%");
        });
    }
}

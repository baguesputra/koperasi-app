<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\RekeningTabungan;
use App\Models\SettingBungaTabungan;
use App\Models\SettingTabungan;
use App\Services\Security\PinSecurityService;
use App\Services\Tabungan\PerhitunganBungaTabunganService;
use App\Services\Tabungan\TabunganRekeningService;
use App\Services\Tabungan\TabunganTransaksiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TabunganController extends Controller
{
    public function __construct(
        private TabunganRekeningService $rekeningService,
        private TabunganTransaksiService $transaksiService,
        private PerhitunganBungaTabunganService $bungaService,
        private PinSecurityService $pinSecurity,
    ) {}

    /**
     * Ringkasan tabungan anggota, mutasi, pengajuan berjalan, dan riwayat bunga.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $anggota = $user->anggota;
        if (! $anggota) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        $setting = SettingTabungan::current();
        $bungaAktif = SettingBungaTabungan::persentaseAktif();

        $rekeningAktif = $anggota->rekeningTabungan()->where('status', 'aktif')->latest('id')->first();
        $rekeningDiajukan = $anggota->rekeningTabungan()->whereIn('status', RekeningTabungan::STATUS_MENUNGGU)->latest('id')->first();
        $rekeningTerakhir = $rekeningAktif
            ?? $rekeningDiajukan
            ?? $anggota->rekeningTabungan()->latest('id')->first();

        $estimasiBunga = null;
        $ringkasanBunga = ['tahun_ini' => 0.0, 'kumulatif' => 0.0];
        $pengajuanList = [];
        $riwayatTransaksi = [];
        $metaTransaksi = ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0];
        $riwayatBunga = [];

        $jenisFilter = (string) $request->input('jenis', '');

        if ($rekeningAktif) {
            $estimasiBunga = $this->bungaService->estimasiBulanBerjalan($rekeningAktif);

            $tahunIni = (string) now()->year;
            $ringkasanBunga = [
                'tahun_ini' => (float) $rekeningAktif->bungaPeriode()
                    ->where('periode', 'like', "{$tahunIni}-%")
                    ->sum('bunga_bersih'),
                'kumulatif' => (float) $rekeningAktif->bungaPeriode()->sum('bunga_bersih'),
            ];

            $pengajuanList = $rekeningAktif->pengajuanTransaksi()
                ->where(function ($q) {
                    $q->whereIn('status', PengajuanTransaksiTabungan::STATUS_MENUNGGU)
                        ->orWhere(fn ($qq) => $qq->where('status', 'ditolak')->where('updated_at', '>=', now()->subDays(30)))
                        ->orWhere(fn ($qq) => $qq->where('status', 'disetujui')->whereNotNull('bukti_pencairan_path')->where('updated_at', '>=', now()->subDays(30)));
                })
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'jenis' => $p->jenis,
                    'kanal' => $p->kanal,
                    'nominal' => (float) $p->nominal,
                    'tanggal_transaksi' => $p->tanggal_transaksi?->format('Y-m-d'),
                    'status' => $p->status,
                    'catatan_anggota' => $p->catatan_anggota,
                    'catatan_admin' => $p->catatan_admin,
                    'catatan_bendahara' => $p->catatan_bendahara,
                    'catatan_ketua' => $p->catatan_ketua,
                    'catatan_pencairan' => $p->catatan_pencairan,
                    'catatan_pengurus' => $p->catatan_pengurus,
                    'tanggal_pencairan' => $p->tanggal_pencairan?->format('Y-m-d'),
                    'rekening_tujuan' => $p->rekening_tujuan_snapshot,
                    'ada_bukti' => (bool) $p->bukti_path,
                    'bukti_url' => $p->bukti_path ? route('tabungan.bukti.pengajuan', ['pengajuan' => $p->id, 'tipe' => 'setor']) : null,
                    'ada_bukti_pencairan' => (bool) $p->bukti_pencairan_path,
                    'bukti_pencairan_url' => $p->bukti_pencairan_path ? route('tabungan.bukti.pengajuan', ['pengajuan' => $p->id, 'tipe' => 'pencairan']) : null,
                    'created_at' => $p->created_at?->format('Y-m-d H:i'),
                ])->values()->all();

            $trxQuery = $rekeningAktif->transaksi()
                ->with(['koreksiDari:id,no_bukti'])
                ->latest('tanggal')
                ->latest('id');

            if (in_array($jenisFilter, ['setor', 'tarik', 'bunga', 'koreksi_masuk', 'koreksi_keluar', 'tutup_rekening'], true)) {
                $trxQuery->where('jenis', $jenisFilter);
            }

            $halaman = $trxQuery->paginate($request->integer('per_page', 15));
            $riwayatTransaksi = $halaman->getCollection()->map(fn ($t) => [
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
                'dibatalkan_pada' => $t->dibatalkan_pada?->format('Y-m-d H:i'),
                'alasan_batal' => $t->alasan_batal,
                'koreksi_dari_no_bukti' => $t->koreksiDari?->no_bukti,
                'ada_bukti' => (bool) $t->bukti_path,
                'bukti_url' => $t->bukti_path ? route('tabungan.bukti.transaksi', $t->id) : null,
            ])->values()->all();

            $metaTransaksi = [
                'current_page' => $halaman->currentPage(),
                'last_page' => $halaman->lastPage(),
                'per_page' => $halaman->perPage(),
                'total' => $halaman->total(),
            ];

            $riwayatBunga = $rekeningAktif->bungaPeriode()
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
                    'pajak_bunga' => (float) $b->pajak_bunga,
                    'bunga_bersih' => (float) $b->bunga_bersih,
                    'diposting_pada' => $b->diposting_pada?->format('Y-m-d H:i'),
                ])->values()->all();
        }

        $rekeningAnggota = $anggota->rekening()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get(['id', 'nama_bank', 'no_rekening', 'atas_nama', 'is_default'])
            ->values()->all();

        $masaTungguBulan = (int) ($setting->masa_tunggu_tarik_bulan ?? 12);

        return response()->json([
            'rekening' => $rekeningTerakhir ? [
                'id' => $rekeningTerakhir->id,
                'no_rekening' => $rekeningTerakhir->no_rekening,
                'status' => $rekeningTerakhir->status,
                'saldo' => (float) $rekeningTerakhir->saldo,
                'suku_bunga_saat_buka' => (float) $rekeningTerakhir->suku_bunga_saat_buka,
                'tanggal_pengajuan' => $rekeningTerakhir->tanggal_pengajuan?->format('Y-m-d'),
                'tanggal_buka' => $rekeningTerakhir->tanggal_buka?->format('Y-m-d'),
                'tanggal_tutup' => $rekeningTerakhir->tanggal_tutup?->format('Y-m-d'),
                'catatan_pengurus' => $rekeningTerakhir->catatan_pengurus,
                'catatan_admin' => $rekeningTerakhir->catatan_admin,
                'catatan_bendahara' => $rekeningTerakhir->catatan_bendahara,
                'catatan_ketua' => $rekeningTerakhir->catatan_ketua,
                'disetujui_admin_pada' => $rekeningTerakhir->disetujui_admin_pada?->format('Y-m-d H:i'),
                'disetujui_bendahara_pada' => $rekeningTerakhir->disetujui_bendahara_pada?->format('Y-m-d H:i'),
                'bisa_tarik' => $rekeningTerakhir->bisaTarik(now(), $masaTungguBulan),
                'tanggal_bisa_tarik' => $rekeningTerakhir->tanggalBisaTarik($masaTungguBulan)?->format('Y-m-d'),
                'sisa_hari_kunci_tarik' => $rekeningTerakhir->sisaHariKunciTarik(now(), $masaTungguBulan),
            ] : null,
            'setting' => [
                'suku_bunga_aktif' => $bungaAktif,
                'metode_bunga' => $setting->metode_bunga,
                'metode_bunga_label' => SettingTabungan::METODE_BUNGA[$setting->metode_bunga] ?? $setting->metode_bunga,
                'min_setor' => (float) $setting->min_setor,
                'saldo_minimal' => (float) $setting->saldo_minimal,
                'min_tarik' => (float) $setting->min_tarik,
                'masa_tunggu_tarik_bulan' => $masaTungguBulan,
                'bank_tujuan_nama' => $setting->bank_tujuan_nama,
                'bank_tujuan_rekening' => $setting->bank_tujuan_rekening,
                'bank_tujuan_atas_nama' => $setting->bank_tujuan_atas_nama,
                'buka_rekening_aktif' => (bool) $setting->buka_rekening_aktif,
            ],
            'syarat' => [
                'versi' => config('syarat_tabungan.versi'),
                'poin' => config('syarat_tabungan.poin', []),
            ],
            'estimasi_bunga' => $estimasiBunga,
            'ringkasan_bunga' => $ringkasanBunga,
            'pengajuan_list' => $pengajuanList,
            'riwayat_transaksi' => $riwayatTransaksi,
            'meta_transaksi' => $metaTransaksi,
            'riwayat_bunga' => $riwayatBunga,
            'rekening_anggota' => $rekeningAnggota,
            'has_pin' => (bool) $user->hasPin(),
        ]);
    }

    /**
     * Ajukan pembukaan rekening tabungan anggota.
     */
    public function buka(Request $request): JsonResponse
    {
        $anggota = $request->user()->anggota;
        if (! $anggota) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        $request->validate([
            'setuju_syarat' => ['required', 'accepted'],
        ], [
            'setuju_syarat.accepted' => 'Anda wajib menyetujui ketentuan Tabungan Anggota.',
        ]);

        try {
            $rekening = $this->rekeningService->ajukanBuka(
                anggota: $anggota,
                setujuSyarat: true,
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pengajuan pembukaan rekening tabungan berhasil dikirim untuk ditinjau Sekretaris.',
            'data' => [
                'id' => $rekening->id,
                'no_rekening' => $rekening->no_rekening,
                'status' => $rekening->status,
            ],
        ], 201);
    }

    /**
     * Ajukan setor dana tabungan dengan lampiran bukti transfer.
     */
    public function setor(Request $request): JsonResponse
    {
        $setting = SettingTabungan::current();
        $minSetor = (float) $setting->min_setor;

        $data = $request->validate([
            'nominal' => ['required', 'numeric', "min:{$minSetor}", 'max:999999999999'],
            'tanggal_transaksi' => ['required', 'date', 'before_or_equal:today'],
            'bukti' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'nominal.min' => 'Minimal setoran adalah Rp '.number_format($minSetor, 0, ',', '.').'.',
            'tanggal_transaksi.before_or_equal' => 'Tanggal transfer tidak boleh melebihi hari ini.',
            'bukti.required' => 'Bukti transfer wajib dilampirkan.',
            'bukti.mimes' => 'Bukti transfer harus berupa JPG, PNG, WEBP, atau PDF.',
            'bukti.max' => 'Ukuran bukti transfer maksimal 5 MB.',
        ]);

        $rekening = $request->user()->anggota?->rekeningTabunganAktif();
        if (! $rekening) {
            return response()->json(['message' => 'Anda belum memiliki rekening tabungan aktif.'], 422);
        }

        try {
            $pengajuan = $this->transaksiService->ajukanSetor(
                rekening: $rekening,
                nominal: (float) $data['nominal'],
                tanggalTransaksi: (string) $data['tanggal_transaksi'],
                bukti: $request->file('bukti'),
                catatan: $data['catatan'] ?? null,
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Setoran tabungan berhasil diajukan. Menunggu verifikasi Bendahara.',
            'data' => [
                'id' => $pengajuan->id,
                'status' => $pengajuan->status,
                'nominal' => (float) $pengajuan->nominal,
            ],
        ], 201);
    }

    /**
     * Ajukan penarikan atau penutupan rekening tabungan (verifikasi PIN jika aktif).
     */
    public function tarik(Request $request): JsonResponse
    {
        $tutup = $request->boolean('tutup_rekening');

        $data = $request->validate([
            'tutup_rekening' => ['nullable', 'boolean'],
            'nominal' => [$tutup ? 'nullable' : 'required', 'numeric', 'min:0', 'max:999999999999'],
            'kanal' => ['required', 'in:transfer,tunai'],
            'rekening_anggota_id' => ['required_if:kanal,transfer', 'nullable', 'integer'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'pin' => [$request->user()->hasPin() ? 'required' : 'nullable', 'string', 'size:6'],
        ], [
            'rekening_anggota_id.required_if' => 'Pilih rekening tujuan transfer milik Anda.',
            'pin.required' => 'PIN keamanan 6 digit wajib diisi.',
            'pin.size' => 'PIN harus tepat 6 digit.',
        ]);

        $user = $request->user();
        if ($user->hasPin()) {
            $hasilPin = $this->pinSecurity->verify($user, (string) $data['pin'], $request);
            if (! $hasilPin['valid']) {
                return response()->json([
                    'message' => $hasilPin['message'],
                    'locked_out' => (bool) ($hasilPin['locked_out'] ?? false),
                    'errors' => ['pin' => [$hasilPin['message']]],
                ], $hasilPin['locked_out'] ? 423 : 422);
            }
        }

        $rekening = $user->anggota?->rekeningTabunganAktif();
        if (! $rekening) {
            return response()->json(['message' => 'Anda belum memiliki rekening tabungan aktif.'], 422);
        }

        try {
            $pengajuan = $this->transaksiService->ajukanTarik(
                rekening: $rekening,
                nominal: (float) ($data['nominal'] ?? 0),
                kanal: (string) $data['kanal'],
                rekeningAnggotaId: isset($data['rekening_anggota_id']) ? (int) $data['rekening_anggota_id'] : null,
                tutupRekening: $tutup,
                catatan: $data['catatan'] ?? null,
                aktorId: $user->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $tutup
                ? 'Pengajuan penutupan rekening & pencairan saldo berhasil dikirim.'
                : 'Pengajuan penarikan tabungan berhasil dikirim.',
            'data' => [
                'id' => $pengajuan->id,
                'jenis' => $pengajuan->jenis,
                'status' => $pengajuan->status,
                'nominal' => (float) $pengajuan->nominal,
            ],
        ], 201);
    }
}

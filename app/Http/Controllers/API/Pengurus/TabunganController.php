<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\RekeningTabungan;
use App\Models\SettingTabungan;
use App\Models\TabunganTransaksi;
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
    ) {}

    /**
     * Setujui pembukaan rekening tabungan sesuai tahap (Sekretaris -> Bendahara -> Ketua).
     */
    public function approveBuka(Request $request, RekeningTabungan $rekening): JsonResponse
    {
        $request->validate(['catatan' => ['nullable', 'string', 'max:255']]);

        $user = $request->user();
        $catatan = $request->input('catatan');

        try {
            $hasil = match ($rekening->status) {
                'diajukan' => $this->guardAndRun(
                    $user->can('tabungan.tinjau-admin'),
                    'Anda tidak memiliki akses tinjauan Sekretaris untuk tabungan.',
                    fn () => $this->rekeningService->approveAdminBuka($rekening, $catatan, $user->id)
                ),
                'approved_admin' => $this->guardAndRun(
                    $user->can('tabungan.tinjau-bendahara') || $user->can('tabungan.kelola'),
                    'Anda tidak memiliki akses tinjauan Bendahara untuk tabungan.',
                    fn () => $this->rekeningService->approveBendaharaBuka($rekening, $catatan, $user->id)
                ),
                'approved_bendahara' => $this->guardAndRun(
                    $user->can('tabungan.approve-ketua'),
                    'Anda tidak memiliki akses persetujuan Ketua untuk tabungan.',
                    fn () => $this->rekeningService->approveKetuaBuka($rekening, $catatan, $user->id)
                ),
                default => throw new RuntimeException('Status pengajuan pembukaan rekening tidak dapat diproses.'),
            };
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $hasil->status === 'aktif'
                ? "Rekening tabungan {$hasil->no_rekening} disetujui dan aktif."
                : 'Tinjauan pembukaan rekening berhasil diteruskan ke tahap berikutnya.',
            'data' => [
                'id' => $hasil->id,
                'no_rekening' => $hasil->no_rekening,
                'status' => $hasil->status,
            ],
        ]);
    }

    /**
     * Tolak pembukaan rekening tabungan dengan catatan wajib.
     */
    public function rejectBuka(Request $request, RekeningTabungan $rekening): JsonResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
            'catatan.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        $user = $request->user();
        [$izin, $tahap] = match ($rekening->status) {
            'diajukan' => [$user->can('tabungan.tinjau-admin'), 'admin'],
            'approved_admin' => [$user->can('tabungan.tinjau-bendahara') || $user->can('tabungan.kelola'), 'bendahara'],
            'approved_bendahara' => [$user->can('tabungan.approve-ketua'), 'ketua'],
            default => [false, null],
        };

        if (! $tahap) {
            return response()->json(['message' => 'Status pembukaan rekening tidak dapat ditolak.'], 422);
        }
        if (! $izin) {
            abort(response()->json(['message' => 'Anda tidak memiliki akses menolak pengajuan ini.'], 403));
        }

        try {
            $hasil = $this->rekeningService->tolakBuka(
                rekening: $rekening,
                catatan: (string) $request->input('catatan'),
                aktorId: $user->id,
                tahap: $tahap,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pengajuan pembukaan rekening tabungan telah ditolak.',
            'data' => [
                'id' => $hasil->id,
                'status' => $hasil->status,
            ],
        ]);
    }

    /**
     * Setujui pengajuan transaksi tabungan (verifikasi setor Bendahara atau approve tarik Sekretaris/Bendahara/Ketua).
     */
    public function approvePengajuan(Request $request, PengajuanTransaksiTabungan $pengajuan): JsonResponse
    {
        $request->validate(['catatan' => ['nullable', 'string', 'max:255']]);

        $user = $request->user();
        $catatan = $request->input('catatan');

        if ($pengajuan->jenis === 'setor') {
            if (! $user->can('tabungan.kelola') && ! $user->can('tabungan.tinjau-bendahara')) {
                abort(response()->json(['message' => 'Anda tidak memiliki akses verifikasi setoran tabungan.'], 403));
            }

            try {
                $trx = $this->transaksiService->setujuiSetor($pengajuan, $catatan, $user->id);
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => "Setoran tabungan berhasil diverifikasi ({$trx->no_bukti}).",
                'data' => [
                    'id' => $pengajuan->id,
                    'status' => 'disetujui',
                    'no_bukti' => $trx->no_bukti,
                ],
            ]);
        }

        try {
            $hasil = match ($pengajuan->status) {
                'menunggu' => $this->guardAndRun(
                    $user->can('tabungan.tinjau-admin'),
                    'Anda tidak memiliki akses tinjauan Sekretaris untuk penarikan tabungan.',
                    fn () => $this->transaksiService->approveAdminTarik($pengajuan, $catatan, $user->id)
                ),
                'approved_admin' => $this->guardAndRun(
                    $user->can('tabungan.tinjau-bendahara') || $user->can('tabungan.kelola'),
                    'Anda tidak memiliki akses tinjauan Bendahara untuk penarikan tabungan.',
                    fn () => $this->transaksiService->approveBendaharaTarik($pengajuan, $catatan, $user->id)
                ),
                'approved_bendahara' => $this->guardAndRun(
                    $user->can('tabungan.approve-ketua'),
                    'Anda tidak memiliki akses persetujuan Ketua untuk penarikan tabungan.',
                    fn () => $this->transaksiService->approveKetuaTarik($pengajuan, $catatan, $user->id)
                ),
                default => throw new RuntimeException('Status pengajuan transaksi tabungan tidak dapat disetujui di tahap ini.'),
            };
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Persetujuan penarikan tabungan berhasil dicatat.',
            'data' => [
                'id' => $hasil->id,
                'status' => $hasil->status,
            ],
        ]);
    }

    /**
     * Cairkan penarikan / penutupan rekening tabungan yang sudah disetujui Ketua (Bendahara).
     */
    public function cairPengajuan(Request $request, PengajuanTransaksiTabungan $pengajuan): JsonResponse
    {
        if (! $request->user()->can('tabungan.kelola')) {
            abort(response()->json(['message' => 'Anda tidak memiliki akses pencairan tabungan.'], 403));
        }

        $data = $request->validate([
            'akun_kas' => ['required', 'in:bank,kas_kecil'],
            'tanggal_pencairan' => ['required', 'date', 'before_or_equal:today'],
            'bukti_pencairan' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'tanggal_pencairan.before_or_equal' => 'Tanggal pencairan tidak boleh melebihi hari ini.',
            'bukti_pencairan.required' => 'Bukti transfer/pencairan wajib dilampirkan.',
            'bukti_pencairan.mimes' => 'Bukti pencairan harus berupa JPG, PNG, WEBP, atau PDF.',
            'bukti_pencairan.max' => 'Ukuran bukti pencairan maksimal 5 MB.',
        ]);

        try {
            $trx = $this->transaksiService->cairkanTarik(
                pengajuan: $pengajuan,
                akunKas: (string) $data['akun_kas'],
                buktiPencairan: $request->file('bukti_pencairan'),
                catatan: $data['catatan'] ?? null,
                aktorId: $request->user()->id,
                tanggalPencairan: (string) $data['tanggal_pencairan'],
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $pengajuan->jenis === 'tutup_rekening'
                ? 'Penutupan rekening & pencairan saldo tabungan berhasil diproses.'
                : 'Penarikan tabungan berhasil dicairkan.',
            'data' => [
                'id' => $pengajuan->id,
                'status' => 'disetujui',
                'no_bukti' => $trx?->no_bukti,
            ],
        ]);
    }

    /**
     * Tolak pengajuan setor/tarik/tutup tabungan.
     */
    public function rejectPengajuan(Request $request, PengajuanTransaksiTabungan $pengajuan): JsonResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
            'catatan.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        $user = $request->user();

        if ($pengajuan->jenis === 'setor') {
            if (! $user->can('tabungan.kelola') && ! $user->can('tabungan.tinjau-bendahara')) {
                abort(response()->json(['message' => 'Anda tidak memiliki akses menolak setoran tabungan.'], 403));
            }
            $tahap = null;
        } else {
            [$izin, $tahap] = match ($pengajuan->status) {
                'menunggu' => [$user->can('tabungan.tinjau-admin'), 'admin'],
                'approved_admin' => [$user->can('tabungan.tinjau-bendahara') || $user->can('tabungan.kelola'), 'bendahara'],
                'approved_bendahara' => [$user->can('tabungan.approve-ketua'), 'ketua'],
                'approved_ketua' => [$user->can('tabungan.kelola'), 'pencairan'],
                default => [false, null],
            };
            if (! $tahap) {
                return response()->json(['message' => 'Status pengajuan transaksi tabungan tidak dapat ditolak.'], 422);
            }
            if (! $izin) {
                abort(response()->json(['message' => 'Anda tidak memiliki akses menolak pengajuan ini.'], 403));
            }
        }

        try {
            $hasil = $this->transaksiService->tolakPengajuan(
                pengajuan: $pengajuan,
                catatan: (string) $request->input('catatan'),
                aktorId: $user->id,
                tahap: $tahap,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pengajuan transaksi tabungan telah ditolak.',
            'data' => [
                'id' => $hasil->id,
                'status' => $hasil->status,
            ],
        ]);
    }

    /**
     * Catat setor tunai langsung oleh Bendahara untuk rekening tabungan aktif.
     */
    public function setorTunai(Request $request): JsonResponse
    {
        if (! $request->user()->can('tabungan.kelola')) {
            abort(response()->json(['message' => 'Anda tidak memiliki akses mencatat setor tunai tabungan.'], 403));
        }

        $setting = SettingTabungan::current();
        $minSetor = (float) $setting->min_setor;

        $data = $request->validate([
            'rekening_tabungan_id' => ['required', 'integer', 'exists:rekening_tabungan,id'],
            'nominal' => ['required', 'numeric', "min:{$minSetor}", 'max:999999999999'],
            'akun_kas' => ['required', 'in:bank,kas_kecil'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'nominal.min' => 'Minimal setoran adalah Rp '.number_format($minSetor, 0, ',', '.').'.',
        ]);

        $rekening = RekeningTabungan::findOrFail($data['rekening_tabungan_id']);

        try {
            $trx = $this->transaksiService->catatSetorTunai(
                rekening: $rekening,
                nominal: (float) $data['nominal'],
                akunKas: (string) $data['akun_kas'],
                tanggal: (string) $data['tanggal'],
                keterangan: $data['keterangan'] ?? null,
                bukti: $request->file('bukti'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Setoran tunai tabungan berhasil dicatat ({$trx->no_bukti}).",
            'data' => [
                'id' => $trx->id,
                'no_bukti' => $trx->no_bukti,
                'nominal' => (float) $trx->nominal,
            ],
        ], 201);
    }

    /**
     * Batalkan (storno) transaksi tabungan setor/tarik oleh Bendahara.
     */
    public function batalkanTransaksi(Request $request, TabunganTransaksi $transaksi): JsonResponse
    {
        if (! $request->user()->can('tabungan.kelola')) {
            abort(response()->json(['message' => 'Anda tidak memiliki akses storno transaksi tabungan.'], 403));
        }

        $request->validate([
            'alasan' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min' => 'Alasan pembatalan minimal 3 karakter.',
        ]);

        try {
            $koreksi = $this->transaksiService->batalkanTransaksi(
                transaksi: $transaksi,
                alasan: (string) $request->input('alasan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Transaksi tabungan berhasil dibatalkan melalui jurnal pembalik (storno).',
            'data' => [
                'id' => $koreksi->id,
                'no_bukti' => $koreksi->no_bukti,
            ],
        ]);
    }

    /**
     * Preview perhitungan bunga bulanan tabungan untuk suatu periode (YYYY-MM).
     */
    public function previewBunga(Request $request): JsonResponse
    {
        if (! $request->user()->can('tabungan.kelola')) {
            abort(response()->json(['message' => 'Anda tidak memiliki akses preview bunga tabungan.'], 403));
        }

        $periode = (string) ($request->input('periode') ?: now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $periode)) {
            return response()->json(['message' => 'Format periode harus YYYY-MM.'], 422);
        }

        return response()->json($this->bungaService->previewPostingPeriode($periode));
    }

    /**
     * Posting bunga bulanan tabungan ke semua rekening aktif.
     */
    public function postingBunga(Request $request): JsonResponse
    {
        if (! $request->user()->can('tabungan.kelola')) {
            abort(response()->json(['message' => 'Anda tidak memiliki akses posting bunga tabungan.'], 403));
        }

        $data = $request->validate([
            'periode' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        try {
            $hasil = $this->bungaService->postingPeriode(
                periode: (string) $data['periode'],
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Posting bunga periode {$hasil['periode']} selesai: {$hasil['diproses']} rekening (total Rp ".number_format($hasil['total_bunga'], 0, ',', '.').').',
            'data' => $hasil,
        ]);
    }

    private function guardAndRun(bool $allowed, string $forbiddenMessage, callable $callback): mixed
    {
        if (! $allowed) {
            abort(response()->json(['message' => $forbiddenMessage], 403));
        }

        return $callback();
    }
}

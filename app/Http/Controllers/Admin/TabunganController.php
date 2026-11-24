<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\RekeningTabungan;
use App\Services\Tabungan\TabunganRekeningService;
use App\Services\Tabungan\TabunganTransaksiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class TabunganController extends Controller
{
    public function __construct(
        private TabunganRekeningService $rekeningService,
        private TabunganTransaksiService $transaksiService,
    ) {}

    public function index(Request $request): Response
    {
        $tabAktif = (string) $request->input('tab', 'buka');
        if (! in_array($tabAktif, ['buka', 'tarik'], true)) {
            $tabAktif = 'buka';
        }

        $antreanBuka = RekeningTabungan::with('anggota:id,nama,no_karyawan,cabang,foto_url,tanggal_jadi_anggota')
            ->where('status', 'diajukan')
            ->orderBy('tanggal_pengajuan')
            ->orderBy('id')
            ->get()
            ->map(fn (RekeningTabungan $r) => [
                'id' => $r->id,
                'status' => $r->status,
                'suku_bunga_saat_buka' => (float) $r->suku_bunga_saat_buka,
                'versi_syarat' => $r->versi_syarat,
                'tanggal_pengajuan' => $r->tanggal_pengajuan?->format('d M Y'),
                'anggota' => $r->anggota ? [
                    'id' => $r->anggota->id,
                    'nama' => $r->anggota->nama,
                    'no_karyawan' => $r->anggota->no_karyawan,
                    'cabang' => $r->anggota->cabang,
                    'foto_url' => $r->anggota->foto_url,
                    'tanggal_jadi_anggota' => $r->anggota->tanggal_jadi_anggota?->format('d M Y'),
                ] : null,
            ]);

        $antreanTarik = PengajuanTransaksiTabungan::with([
            'anggota:id,nama,no_karyawan,cabang,foto_url',
            'rekening:id,no_rekening,saldo,tanggal_buka',
        ])
            ->where('status', 'menunggu')
            ->whereIn('jenis', PengajuanTransaksiTabungan::JENIS_PENARIKAN)
            ->orderBy('created_at')
            ->get()
            ->map(fn (PengajuanTransaksiTabungan $p) => [
                'id' => $p->id,
                'rekening_id' => $p->rekening_tabungan_id,
                'no_rekening' => $p->rekening?->no_rekening,
                'saldo_rekening' => (float) ($p->rekening?->saldo ?? 0),
                'tanggal_buka' => $p->rekening?->tanggal_buka?->format('d M Y'),
                'jenis' => $p->jenis,
                'kanal' => $p->kanal,
                'nominal' => (float) $p->nominal,
                'tanggal_transaksi' => $p->tanggal_transaksi?->format('d M Y'),
                'catatan_anggota' => $p->catatan_anggota,
                'rekening_tujuan' => $p->rekening_tujuan_snapshot,
                'status' => $p->status,
                'created_at' => $p->created_at?->format('d M Y H:i'),
                'anggota' => $p->anggota ? [
                    'id' => $p->anggota->id,
                    'nama' => $p->anggota->nama,
                    'no_karyawan' => $p->anggota->no_karyawan,
                    'cabang' => $p->anggota->cabang,
                    'foto_url' => $p->anggota->foto_url,
                ] : null,
            ]);

        return Inertia::render('Admin/Tabungan/Index', [
            'tabAktif' => $tabAktif,
            'antreanBuka' => $antreanBuka,
            'antreanTarik' => $antreanTarik,
            'counts' => [
                'buka' => $antreanBuka->count(),
                'tarik' => $antreanTarik->count(),
            ],
        ]);
    }

    public function approveBuka(Request $request, RekeningTabungan $rekening): RedirectResponse
    {
        $request->validate(['catatan' => ['nullable', 'string', 'max:255']]);

        try {
            $this->rekeningService->approveAdminBuka(
                rekening: $rekening,
                catatan: $request->input('catatan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        return back()->with('status', 'Verifikasi Sekretaris selesai. Pengajuan diteruskan ke Bendahara.');
    }

    public function rejectBuka(Request $request, RekeningTabungan $rekening): RedirectResponse
    {
        $request->validate(['catatan' => ['required', 'string', 'min:3', 'max:255']]);

        try {
            $this->rekeningService->tolakBuka(
                rekening: $rekening,
                catatan: (string) $request->input('catatan'),
                aktorId: $request->user()->id,
                tahap: 'admin',
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['catatan' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan pembukaan rekening tabungan telah ditolak.');
    }

    public function approveTarik(Request $request, PengajuanTransaksiTabungan $pengajuan): RedirectResponse
    {
        $request->validate(['catatan' => ['nullable', 'string', 'max:255']]);

        try {
            $this->transaksiService->approveAdminTarik(
                pengajuan: $pengajuan,
                catatan: $request->input('catatan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        return back()->with('status', 'Verifikasi Sekretaris selesai. Pengajuan penarikan diteruskan ke Bendahara.');
    }

    public function rejectTarik(Request $request, PengajuanTransaksiTabungan $pengajuan): RedirectResponse
    {
        $request->validate(['catatan' => ['required', 'string', 'min:3', 'max:255']]);

        try {
            $this->transaksiService->tolakPengajuan(
                pengajuan: $pengajuan,
                catatan: (string) $request->input('catatan'),
                aktorId: $request->user()->id,
                tahap: 'admin',
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['catatan' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan penarikan tabungan telah ditolak.');
    }
}

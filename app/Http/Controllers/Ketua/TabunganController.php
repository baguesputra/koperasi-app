<?php

namespace App\Http\Controllers\Ketua;

use App\Http\Controllers\Controller;
use App\Models\KasKoperasi;
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

        $antreanBuka = RekeningTabungan::with([
            'anggota:id,nama,no_karyawan,cabang,foto_url,tanggal_jadi_anggota',
            'adminOleh:id,name',
            'bendaharaOleh:id,name',
        ])
            ->where('status', 'approved_bendahara')
            ->orderBy('tanggal_pengajuan')
            ->orderBy('id')
            ->get()
            ->map(fn (RekeningTabungan $r) => [
                'id' => $r->id,
                'status' => $r->status,
                'suku_bunga_saat_buka' => (float) $r->suku_bunga_saat_buka,
                'versi_syarat' => $r->versi_syarat,
                'tanggal_pengajuan' => $r->tanggal_pengajuan?->format('d M Y'),
                'admin_oleh' => $r->adminOleh?->name,
                'catatan_admin' => $r->catatan_admin,
                'disetujui_admin_pada' => $r->disetujui_admin_pada?->format('d M Y H:i'),
                'bendahara_oleh' => $r->bendaharaOleh?->name,
                'catatan_bendahara' => $r->catatan_bendahara,
                'disetujui_bendahara_pada' => $r->disetujui_bendahara_pada?->format('d M Y H:i'),
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
            'adminOleh:id,name',
            'bendaharaOleh:id,name',
        ])
            ->where('status', 'approved_bendahara')
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
                'admin_oleh' => $p->adminOleh?->name,
                'catatan_admin' => $p->catatan_admin,
                'disetujui_admin_pada' => $p->disetujui_admin_pada?->format('d M Y H:i'),
                'bendahara_oleh' => $p->bendaharaOleh?->name,
                'catatan_bendahara' => $p->catatan_bendahara,
                'disetujui_bendahara_pada' => $p->disetujui_bendahara_pada?->format('d M Y H:i'),
                'created_at' => $p->created_at?->format('d M Y H:i'),
                'anggota' => $p->anggota ? [
                    'id' => $p->anggota->id,
                    'nama' => $p->anggota->nama,
                    'no_karyawan' => $p->anggota->no_karyawan,
                    'cabang' => $p->anggota->cabang,
                    'foto_url' => $p->anggota->foto_url,
                ] : null,
            ]);

        $kas = KasKoperasi::first();

        return Inertia::render('Ketua/Tabungan/Index', [
            'tabAktif' => $tabAktif,
            'antreanBuka' => $antreanBuka,
            'antreanTarik' => $antreanTarik,
            'kasFisik' => [
                'saldo_bank' => (float) ($kas?->saldo_bank ?? 0),
                'saldo_kas_kecil' => (float) ($kas?->saldo_kas_kecil ?? 0),
            ],
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
            $rek = $this->rekeningService->approveKetuaBuka(
                rekening: $rekening,
                catatan: $request->input('catatan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        return back()->with('status', "Rekening tabungan {$rek->no_rekening} disetujui Ketua dan resmi aktif.");
    }

    public function rejectBuka(Request $request, RekeningTabungan $rekening): RedirectResponse
    {
        $request->validate(['catatan' => ['required', 'string', 'min:3', 'max:255']]);

        try {
            $this->rekeningService->tolakBuka(
                rekening: $rekening,
                catatan: (string) $request->input('catatan'),
                aktorId: $request->user()->id,
                tahap: 'ketua',
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['catatan' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan pembukaan rekening tabungan telah ditolak Ketua Koperasi.');
    }

    public function approveTarik(Request $request, PengajuanTransaksiTabungan $pengajuan): RedirectResponse
    {
        $request->validate(['catatan' => ['nullable', 'string', 'max:255']]);

        try {
            $this->transaksiService->approveKetuaTarik(
                pengajuan: $pengajuan,
                catatan: $request->input('catatan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan penarikan disetujui Ketua. Diteruskan ke Bendahara untuk pencairan & unggah bukti transfer.');
    }

    public function rejectTarik(Request $request, PengajuanTransaksiTabungan $pengajuan): RedirectResponse
    {
        $request->validate(['catatan' => ['required', 'string', 'min:3', 'max:255']]);

        try {
            $this->transaksiService->tolakPengajuan(
                pengajuan: $pengajuan,
                catatan: (string) $request->input('catatan'),
                aktorId: $request->user()->id,
                tahap: 'ketua',
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['catatan' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan penarikan tabungan telah ditolak Ketua Koperasi.');
    }
}

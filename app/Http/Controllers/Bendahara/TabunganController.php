<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\KasKoperasi;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\RekeningTabungan;
use App\Models\SettingBungaTabungan;
use App\Models\SettingTabungan;
use App\Models\TabunganTransaksi;
use App\Services\Tabungan\PerhitunganBungaTabunganService;
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
        private PerhitunganBungaTabunganService $bungaService,
    ) {}

    public function index(Request $request): Response
    {
        $tabAktif = (string) $request->input('tab', 'setor');
        if (! in_array($tabAktif, ['buka', 'setor', 'tarik', 'bunga'], true)) {
            $tabAktif = 'setor';
        }

        $periodeBunga = (string) $request->input('periode', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $periodeBunga)) {
            $periodeBunga = now()->format('Y-m');
        }

        $antreanBuka = RekeningTabungan::with('anggota:id,nama,no_karyawan,cabang,foto_url,tanggal_jadi_anggota')
            ->where('status', 'diajukan')
            ->orderBy('tanggal_pengajuan')
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => [
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

        $mapPengajuan = fn (PengajuanTransaksiTabungan $p) => [
            'id' => $p->id,
            'rekening_id' => $p->rekening_tabungan_id,
            'no_rekening' => $p->rekening?->no_rekening,
            'saldo_rekening' => (float) ($p->rekening?->saldo ?? 0),
            'jenis' => $p->jenis,
            'kanal' => $p->kanal,
            'nominal' => (float) $p->nominal,
            'tanggal_transaksi' => $p->tanggal_transaksi?->format('d M Y'),
            'catatan_anggota' => $p->catatan_anggota,
            'rekening_tujuan' => $p->rekening_tujuan_snapshot,
            'status' => $p->status,
            'ada_bukti' => (bool) $p->bukti_path,
            'bukti_mime' => $p->bukti_mime,
            'bukti_nama_asli' => $p->bukti_nama_asli,
            'bukti_url' => $p->bukti_path ? route('tabungan.bukti.pengajuan', ['pengajuan' => $p->id, 'tipe' => 'setor']) : null,
            'created_at' => $p->created_at?->format('d M Y H:i'),
            'anggota' => $p->anggota ? [
                'id' => $p->anggota->id,
                'nama' => $p->anggota->nama,
                'no_karyawan' => $p->anggota->no_karyawan,
                'cabang' => $p->anggota->cabang,
                'foto_url' => $p->anggota->foto_url,
            ] : null,
        ];

        $antreanSetor = PengajuanTransaksiTabungan::with([
            'anggota:id,nama,no_karyawan,cabang,foto_url',
            'rekening:id,no_rekening,saldo',
        ])
            ->where('status', 'menunggu')
            ->where('jenis', 'setor')
            ->orderBy('created_at')
            ->get()
            ->map($mapPengajuan);

        $antreanTarik = PengajuanTransaksiTabungan::with([
            'anggota:id,nama,no_karyawan,cabang,foto_url',
            'rekening:id,no_rekening,saldo',
        ])
            ->where('status', 'menunggu')
            ->whereIn('jenis', ['tarik', 'tutup_rekening'])
            ->orderBy('created_at')
            ->get()
            ->map($mapPengajuan);

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

        $kas = KasKoperasi::first();
        $setting = SettingTabungan::current();

        return Inertia::render('Bendahara/Tabungan/Index', [
            'tabAktif' => $tabAktif,
            'periodeBunga' => $periodeBunga,
            'antreanBuka' => $antreanBuka,
            'antreanSetor' => $antreanSetor,
            'antreanTarik' => $antreanTarik,
            'previewBunga' => $tabAktif === 'bunga' ? $this->bungaService->previewPostingPeriode($periodeBunga) : null,
            'rekeningAktifOptions' => $rekeningAktifOptions,
            'kasFisik' => [
                'saldo_bank' => (float) ($kas?->saldo_bank ?? 0),
                'saldo_kas_kecil' => (float) ($kas?->saldo_kas_kecil ?? 0),
            ],
            'setting' => [
                'min_setor' => (float) $setting->min_setor,
                'saldo_minimal' => (float) $setting->saldo_minimal,
                'suku_bunga_aktif' => SettingBungaTabungan::persentaseAktif(),
            ],
            'counts' => [
                'buka' => $antreanBuka->count(),
                'setor' => $antreanSetor->count(),
                'tarik' => $antreanTarik->count(),
            ],
        ]);
    }

    public function setujuiBuka(Request $request, RekeningTabungan $rekening): RedirectResponse
    {
        $request->validate(['catatan' => ['nullable', 'string', 'max:255']]);

        try {
            $rek = $this->rekeningService->setujuiBuka(
                rekening: $rekening,
                catatan: $request->input('catatan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        return back()->with('status', "Rekening tabungan {$rek->no_rekening} berhasil disetujui dan aktif.");
    }

    public function tolakBuka(Request $request, RekeningTabungan $rekening): RedirectResponse
    {
        $request->validate(['catatan' => ['required', 'string', 'min:3', 'max:255']]);

        try {
            $this->rekeningService->tolakBuka(
                rekening: $rekening,
                catatan: (string) $request->input('catatan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['catatan' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan pembukaan rekening tabungan telah ditolak.');
    }

    public function setujuiPengajuan(Request $request, PengajuanTransaksiTabungan $pengajuan): RedirectResponse
    {
        if ($pengajuan->jenis === 'setor') {
            $request->validate(['catatan' => ['nullable', 'string', 'max:255']]);

            try {
                $trx = $this->transaksiService->setujuiSetor(
                    pengajuan: $pengajuan,
                    catatan: $request->input('catatan'),
                    aktorId: $request->user()->id,
                );
            } catch (RuntimeException $e) {
                return back()->withErrors(['aksi' => $e->getMessage()]);
            }

            return back()->with('status', "Setoran tabungan berhasil diverifikasi ({$trx->no_bukti}).");
        }

        $request->validate([
            'akun_kas' => ['required', 'in:bank,kas_kecil'],
            'bukti_pencairan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->transaksiService->setujuiTarik(
                pengajuan: $pengajuan,
                akunKas: (string) $request->input('akun_kas'),
                buktiPencairan: $request->file('bukti_pencairan'),
                catatan: $request->input('catatan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        $pesan = $pengajuan->jenis === 'tutup_rekening'
            ? 'Penutupan rekening dan pencairan saldo tabungan berhasil diproses.'
            : 'Penarikan tabungan berhasil dicairkan.';

        return back()->with('status', $pesan);
    }

    public function tolakPengajuan(Request $request, PengajuanTransaksiTabungan $pengajuan): RedirectResponse
    {
        $request->validate(['catatan' => ['required', 'string', 'min:3', 'max:255']]);

        try {
            $this->transaksiService->tolakPengajuan(
                pengajuan: $pengajuan,
                catatan: (string) $request->input('catatan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['catatan' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan transaksi tabungan telah ditolak.');
    }

    public function setorTunai(Request $request): RedirectResponse
    {
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
            return back()->withErrors(['setor_tunai' => $e->getMessage()]);
        }

        return back()->with('status', "Setoran tunai tabungan berhasil dicatat ({$trx->no_bukti}).");
    }

    public function batalkanTransaksi(Request $request, TabunganTransaksi $transaksi): RedirectResponse
    {
        $request->validate([
            'alasan' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        try {
            $this->transaksiService->batalkanTransaksi(
                transaksi: $transaksi,
                alasan: (string) $request->input('alasan'),
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['alasan' => $e->getMessage()]);
        }

        return back()->with('status', 'Transaksi tabungan berhasil dibatalkan melalui jurnal pembalik (storno).');
    }

    public function postingBunga(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'periode' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        try {
            $hasil = $this->bungaService->postingPeriode(
                periode: (string) $data['periode'],
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['periode' => $e->getMessage()]);
        }

        return back()->with(
            'status',
            "Posting bunga periode {$hasil['periode']} selesai: {$hasil['diproses']} rekening diproses (total Rp ".number_format($hasil['total_bunga'], 0, ',', '.').').'
        );
    }
}

<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\PengajuanPinjamanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PinjamanController extends Controller
{
    public function __construct(
        private EligibilitasPinjamanService $eligibilitas,
        private PengajuanPinjamanService $pengajuan,
    ) {}

    public function create(): Response
    {
        $anggota = auth()->user()->anggota;
        $cek = $this->eligibilitas->cek($anggota);

        return Inertia::render('Portal/Pinjaman/Create', [
            'bisaAjukan' => $cek['boleh'],
            'alasanTidakBisa' => $cek['alasan'],
            'limitMaksimal' => $this->eligibilitas->limitMaksimal($anggota),
            'limitTersedia' => (float) $cek['limit_tersedia'],
            'sisaAngsuranAktif' => (int) $cek['sisa_angsuran'],
            'cicilanPokokAktif' => (float) $cek['cicilan_pokok'],
            'rekeningTersimpan' => $anggota->rekening()->orderByDesc('is_default')->get([
                'id', 'nama_bank', 'no_rekening', 'atas_nama', 'is_default',
            ]),
            'poinSyarat' => config('syarat_pinjaman.poin'),
            'versiSyarat' => config('syarat_pinjaman.versi'),
        ]);
    }

    public function cekNominal(Request $request)
    {
        $request->validate(['nominal' => ['required', 'numeric', 'min:1']]);

        $anggota = auth()->user()->anggota;
        $nominal = (float) $request->nominal;
        $limitTersedia = $this->eligibilitas->limitTersedia($anggota);

        if ($nominal > $limitTersedia) {
            return response()->json([
                'valid' => false,
                'pesan' => 'Nominal melebihi limit tersedia Anda: Rp '.number_format($limitTersedia, 0, ',', '.'),
            ], 422);
        }

        $tenorMaksimal = $this->eligibilitas->tenorMaksimal($nominal);

        if (! $tenorMaksimal) {
            return response()->json([
                'valid' => false,
                'pesan' => 'Ketentuan tenor untuk nominal ini belum diatur. Silakan hubungi Admin koperasi.',
            ], 422);
        }

        return response()->json(['valid' => true, 'tenor_maksimal' => $tenorMaksimal]);
    }

    public function simulasi(Request $request)
    {
        $request->validate([
            'nominal' => ['required', 'numeric', 'min:1'],
            'tenor_bulan' => ['required', 'integer', 'min:1'],
        ]);

        $jadwal = $this->pengajuan->preview((float) $request->nominal, (int) $request->tenor_bulan);

        return response()->json([
            'jadwal' => $jadwal,
            'total_dibayar' => array_sum(array_column($jadwal, 'total_bayar')),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nominal' => ['required', 'numeric', 'min:1'],
            'tenor_bulan' => ['required', 'integer', 'min:1'],
            'keperluan' => ['required', 'string', 'min:5', 'max:500'],
            'rekening_mode' => ['required', 'in:tersimpan,baru'],
            'rekening_id' => ['required_if:rekening_mode,tersimpan', 'nullable', 'integer'],
            'nama_bank' => ['required_if:rekening_mode,baru', 'nullable', 'string', 'max:100'],
            'no_rekening' => ['required_if:rekening_mode,baru', 'nullable', 'string', 'max:50'],
            'atas_nama' => ['required_if:rekening_mode,baru', 'nullable', 'string', 'max:100'],
            'persetujuan' => ['required', 'accepted'],
        ], [
            'persetujuan.required' => 'Anda harus menyetujui seluruh syarat & ketentuan pinjaman terlebih dahulu.',
            'persetujuan.accepted' => 'Anda harus menyetujui seluruh syarat & ketentuan pinjaman terlebih dahulu.',
        ]);

        try {
            $this->pengajuan->ajukan(
                auth()->user(),
                (float) $request->nominal,
                (int) $request->tenor_bulan,
                $request->keperluan,
                [
                    'mode' => $request->rekening_mode,
                    'rekening_id' => $request->rekening_id,
                    'nama_bank' => $request->nama_bank,
                    'no_rekening' => $request->no_rekening,
                    'atas_nama' => $request->atas_nama,
                ],
                [
                    'persetujuan' => true,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['pengajuan' => $e->getMessage()]);
        }

        return redirect()
            ->route('portal.dashboard')
            ->with('pinjaman_terkirim', [
                'nominal' => (float) $request->nominal,
                'tenor_bulan' => (int) $request->tenor_bulan,
            ]);
    }
}

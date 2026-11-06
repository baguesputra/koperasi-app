<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLimit;
use App\Models\Pinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Pinjaman\PengajuanPinjamanService;
use App\Services\Security\PinSecurityService;
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

        if ($this->hasLimitApplicationInProgress($anggota->id)) {
            abort(403, 'Pengajuan kenaikan limit sedang diproses.');
        }

        if ($this->hasLoanApplicationInProgress($anggota->id)) {
            abort(403, 'Pengajuan pinjaman sedang diproses.');
        }

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

        if ($this->hasLimitApplicationInProgress($anggota->id)) {
            return response()->json([
                'valid' => false,
                'pesan' => 'Pengajuan kenaikan limit sedang diproses.',
            ], 422);
        }

        if ($this->hasLoanApplicationInProgress($anggota->id)) {
            return response()->json([
                'valid' => false,
                'pesan' => 'Pengajuan pinjaman sedang diproses.',
            ], 422);
        }

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
        $user = auth()->user();

        $rules = [
            'nominal' => ['required', 'numeric', 'min:1'],
            'tenor_bulan' => ['required', 'integer', 'min:1'],
            'keperluan' => ['required', 'string', 'min:5', 'max:500'],
            'rekening_mode' => ['required', 'in:tersimpan,baru'],
            'rekening_id' => ['required_if:rekening_mode,tersimpan', 'nullable', 'integer'],
            'nama_bank' => ['required_if:rekening_mode,baru', 'nullable', 'string', 'max:100'],
            'no_rekening' => ['required_if:rekening_mode,baru', 'nullable', 'string', 'max:50'],
            'atas_nama' => ['required_if:rekening_mode,baru', 'nullable', 'string', 'max:100'],
            'persetujuan' => ['required', 'accepted'],
        ];

        if ($user->hasPin()) {
            $rules['pin'] = ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'];
        } else {
            $rules['pin'] = ['nullable', 'string', 'size:6', 'regex:/^[0-9]{6}$/'];
        }

        $request->validate($rules, [
            'persetujuan.required' => 'Anda harus menyetujui seluruh syarat & ketentuan pinjaman terlebih dahulu.',
            'persetujuan.accepted' => 'Anda harus menyetujui seluruh syarat & ketentuan pinjaman terlebih dahulu.',
            'pin.required' => 'PIN keamanan wajib dimasukkan untuk mengonfirmasi pengajuan pinjaman.',
            'pin.size' => 'PIN keamanan harus 6 digit angka.',
            'pin.regex' => 'PIN keamanan hanya boleh berisi angka.',
        ]);

        if ($user->hasPin()) {
            $pinCheck = app(PinSecurityService::class)->verify($user, (string) $request->pin, $request);
            if (! $pinCheck['valid']) {
                if ($pinCheck['locked_out']) {
                    return redirect()->route('login')->withErrors([
                        'pin' => $pinCheck['message'],
                    ]);
                }

                return back()->withErrors([
                    'pin' => $pinCheck['message'],
                ]);
            }
        }

        $anggota = auth()->user()->anggota;

        if ($this->hasLimitApplicationInProgress($anggota->id)) {
            return back()->withErrors([
                'nominal' => 'Pengajuan kenaikan limit sedang diproses.',
                'pengajuan' => 'Pengajuan kenaikan limit sedang diproses.',
            ]);
        }

        if ($this->hasLoanApplicationInProgress($anggota->id)) {
            return back()->withErrors([
                'nominal' => 'Pengajuan pinjaman sedang diproses.',
                'pengajuan' => 'Pengajuan pinjaman sedang diproses.',
            ]);
        }

        $nominal = (float) $request->nominal;

        $cekEligibilitas = $this->eligibilitas->cek($anggota);
        if (! $cekEligibilitas['boleh']) {
            return back()->withErrors([
                'nominal' => $cekEligibilitas['alasan'],
                'pengajuan' => $cekEligibilitas['alasan'],
            ]);
        }

        $limitTersedia = (float) $cekEligibilitas['limit_tersedia'];
        if ($nominal > $limitTersedia) {
            $pesan = 'Nominal pinjaman melebihi limit tersedia Anda: Rp '.number_format($limitTersedia, 0, ',', '.');

            return back()->withErrors([
                'nominal' => $pesan,
                'pengajuan' => $pesan,
            ]);
        }

        $plafonMaksimalKoperasi = $this->eligibilitas->plafonTertinggiKoperasi();
        if ($nominal > $plafonMaksimalKoperasi) {
            $pesan = 'Nominal pinjaman melebihi batas kebijakan koperasi.';

            return back()->withErrors([
                'nominal' => $pesan,
                'pengajuan' => $pesan,
            ]);
        }

        $tenorMaksimal = $this->eligibilitas->tenorMaksimal($nominal);
        if (! $tenorMaksimal) {
            return back()->withErrors([
                'nominal' => 'Nominal pinjaman tidak sesuai dengan ketentuan yang berlaku.',
                'pengajuan' => 'Nominal pinjaman tidak sesuai dengan ketentuan yang berlaku.',
            ]);
        }
        if ((int) $request->tenor_bulan > $tenorMaksimal) {
            return back()->withErrors([
                'tenor_bulan' => "Tenor maksimal untuk nominal ini adalah {$tenorMaksimal} bulan.",
                'pengajuan' => "Tenor maksimal untuk nominal ini adalah {$tenorMaksimal} bulan.",
            ]);
        }

        try {
            $this->pengajuan->ajukan(
                auth()->user(),
                $nominal,
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
            return back()->withErrors([
                'nominal' => $e->getMessage(),
                'pengajuan' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('portal.dashboard')
            ->with('pinjaman_terkirim', [
                'nominal' => $nominal,
                'tenor_bulan' => (int) $request->tenor_bulan,
            ]);
    }

    private function hasLimitApplicationInProgress(int $anggotaId): bool
    {
        return PengajuanLimit::where('anggota_id', $anggotaId)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->exists();
    }

    private function hasLoanApplicationInProgress(int $anggotaId): bool
    {
        return Pinjaman::where('anggota_id', $anggotaId)
            ->whereIn('status', ['diajukan', 'approved_admin', 'approved_bendahara'])
            ->exists();
    }
}

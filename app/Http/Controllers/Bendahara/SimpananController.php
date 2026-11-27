<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Services\Simpanan\KonfirmasiSimpananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class SimpananController extends Controller
{
    public function __construct(private KonfirmasiSimpananService $konfirmasi) {}

    public function index(Request $request): Response
    {
        $bulan = (string) $request->input('bulan', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = now()->format('Y-m');
        }

        $bulanIni = now()->format('Y-m');
        $isBulanBerjalan = $bulan === $bulanIni;
        $isBulanLalu = $bulan < $bulanIni;
        $isBulanDepan = $bulan > $bulanIni;

        $cabangAktif = $request->string('cabang');

        if ($isBulanLalu) {
            $semuaBelumSimpanan = collect();
            $semuaTerlambat = collect();
            $dipindahSemua = $this->konfirmasi->simpananWajibTerlambat($bulanIni, null, $bulan);
            $dipindahTampil = $cabangAktif->isNotEmpty()
                ? $dipindahSemua->where('cabang', $cabangAktif)->values()
                : $dipindahSemua;
            $dipindahKeBulanIni = [
                'jumlah' => $dipindahTampil->count(),
                'nominal' => (float) $dipindahTampil->sum('nominal'),
            ];
        } else {
            $semuaTerlambat = $isBulanBerjalan
                ? $this->konfirmasi->simpananWajibTerlambat($bulanIni)
                : $this->konfirmasi->simpananWajibTerlambat($bulan);

            $tertuaTerlambat = [];
            foreach ($semuaTerlambat as $row) {
                $aid = (int) $row['anggota_id'];
                if (! isset($tertuaTerlambat[$aid])) {
                    $tertuaTerlambat[$aid] = $row['bulan_periode_label'];
                }
            }

            $semuaBelumSimpanan = $this->konfirmasi->anggotaBelumSimpananWajib($bulan)
                ->map(function ($a) use ($tertuaTerlambat, $isBulanDepan) {
                    $periodeTertua = $tertuaTerlambat[$a->id] ?? null;
                    $adaTunggakan = $periodeTertua !== null;

                    return [
                        'id' => $a->id,
                        'nama' => $a->nama,
                        'no_anggota' => $a->no_anggota,
                        'no_karyawan' => $a->no_karyawan,
                        'cabang' => $a->cabang,
                        'foto_url' => $a->foto_url,
                        'ada_tunggakan_terlambat' => $adaTunggakan,
                        'periode_terlambat_tertua' => $periodeTertua,
                        'bisa_dipilih' => ! $isBulanDepan && ! $adaTunggakan,
                    ];
                });

            if (! $isBulanBerjalan) {
                $semuaTerlambat = collect();
            }
            $dipindahKeBulanIni = ['jumlah' => 0, 'nominal' => 0.0];
        }

        $belumSimpanan = $cabangAktif->isNotEmpty()
            ? $semuaBelumSimpanan->where('cabang', $cabangAktif)->values()
            : $semuaBelumSimpanan;

        $terlambatSimpanan = $cabangAktif->isNotEmpty()
            ? $semuaTerlambat->where('cabang', $cabangAktif)->values()
            : $semuaTerlambat;

        $semuaSudahSimpanan = $this->konfirmasi->anggotaSudahSimpananWajib($bulan);
        $sudahSimpanan = $cabangAktif->isNotEmpty()
            ? $semuaSudahSimpanan->where('cabang', $cabangAktif)->values()
            : $semuaSudahSimpanan;

        $nominalWajib = (float) Cache::remember('setting_simpanan_wajib', 600, fn () => SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 0);
        $nominalDanaSosial = (float) Cache::remember('setting_simpanan_sosial', 600, fn () => SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 0);
        $nominalPerAnggota = $nominalWajib + $nominalDanaSosial;

        $ringkasanCabang = $semuaBelumSimpanan
            ->groupBy('cabang')
            ->map(fn ($anggota) => [
                'jumlah_anggota' => $anggota->count(),
                'nominal' => (float) ($anggota->count() * $nominalPerAnggota),
            ]);

        $daftarCabang = Cache::remember('daftar_cabang', 600, fn () => Anggota::query()->whereNotNull('cabang')->distinct()->orderBy('cabang')->pluck('cabang')->values()->all());

        $totalDanaSosialTerkumpul = Simpanan::where('jenis', 'dana_sosial')->sum('jumlah');
        $anggotaBelumGenapEnamBulan = $this->konfirmasi->anggotaBaruBelumGenapEnamBulan(
            $cabangAktif->isNotEmpty() ? $cabangAktif->value() : null,
            $bulan,
        );

        $tabAwal = in_array($request->input('tab'), ['belum', 'terlambat', 'sudah', 'dimuka'], true)
            ? (string) $request->input('tab')
            : ($isBulanLalu ? 'sudah' : 'belum');

        return Inertia::render('Bendahara/Simpanan/Index', [
            'bulan' => $bulan,
            'bulanIni' => $bulanIni,
            'isBulanDepan' => $isBulanDepan,
            'tabAwal' => $tabAwal,
            'belumSimpanan' => $belumSimpanan,
            'terlambatSimpanan' => $terlambatSimpanan,
            'sudahSimpanan' => $sudahSimpanan,
            'totalSudahSimpanan' => (float) $semuaSudahSimpanan->sum('nominal_total'),
            'totalTerlambatSimpanan' => (float) $semuaTerlambat->sum('nominal'),
            'dipindahKeBulanIni' => $dipindahKeBulanIni,
            'anggotaBelumGenapEnamBulan' => $anggotaBelumGenapEnamBulan,
            'cabangAktif' => $cabangAktif->value(),
            'daftarCabang' => $daftarCabang,
            'ringkasanCabang' => $ringkasanCabang,
            'nominalPerAnggota' => $nominalPerAnggota,
            'totalDanaSosialTerkumpul' => (float) $totalDanaSosialTerkumpul,
        ]);
    }

    public function konfirmasi(Request $request)
    {
        $request->validate([
            'anggota_ids' => ['required_without:items', 'array', 'min:1'],
            'anggota_ids.*' => ['integer', 'exists:anggota,id'],
            'bulan_periode' => ['required_with:anggota_ids', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'items' => ['required_without:anggota_ids', 'array', 'min:1'],
            'items.*.anggota_id' => ['required', 'integer', 'exists:anggota,id'],
            'items.*.bulan_periode' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        try {
            if ($request->filled('items')) {
                $jumlah = $this->konfirmasi->konfirmasiMassalMultiPeriode(
                    $request->input('items', []),
                    auth()->id()
                );

                return back()->with('status', "Simpanan terlambat {$jumlah} tagihan berhasil dikonfirmasi.");
            }

            $jumlah = $this->konfirmasi->konfirmasiMassal(
                $request->anggota_ids,
                $request->bulan_periode,
                auth()->id()
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['konfirmasi' => $e->getMessage()]);
        }

        return back()->with('status', "Simpanan wajib {$jumlah} anggota berhasil dikonfirmasi.");
    }

    public function bayarDiMuka(Request $request, Anggota $anggota)
    {
        $request->validate([
            'jumlah_bulan' => ['required', 'integer', 'min:1', 'max:6'],
            'mulai_bulan' => ['nullable', 'date_format:Y-m'],
        ]);

        try {
            $hasil = $this->konfirmasi->bayarDiMuka(
                anggotaId: $anggota->id,
                jumlahBulan: (int) $request->jumlah_bulan,
                inputByUserId: auth()->id(),
                mulaiBulan: $request->input('mulai_bulan'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['jumlah_bulan' => $e->getMessage()]);
        }

        return back()->with(
            'status',
            "Simpanan di muka {$hasil['anggota_nama']} ({$hasil['jumlah_bulan']} bulan: ".implode(', ', $hasil['periode']).') sebesar Rp '.number_format($hasil['total_nominal'], 0, ',', '.').' berhasil dicatat.'
        );
    }

    public function batalkan(Request $request, Anggota $anggota)
    {
        $request->validate([
            'bulan_periode' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'alasan' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        try {
            $this->konfirmasi->batalkanKonfirmasi(
                anggotaId: $anggota->id,
                bulanPeriode: (string) $request->bulan_periode,
                alasan: trim((string) $request->alasan),
                userId: auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['alasan' => $e->getMessage()]);
        }

        return back()->with('status', "Konfirmasi simpanan {$anggota->nama} berhasil dibatalkan.");
    }
}

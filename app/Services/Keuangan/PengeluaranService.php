<?php

namespace App\Services\Keuangan;

use App\Models\AuditLog;
use App\Models\Pengeluaran;
use App\Models\SettingKas;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengeluaranService
{
    public function __construct(private JurnalKasService $jurnalKas) {}

    public function catat(string $jenis, float $jumlah, string $keterangan, string $tanggal, int $userId): Pengeluaran
    {
        return DB::transaction(function () use ($jenis, $jumlah, $keterangan, $tanggal, $userId) {
            if ($jenis === 'dana_sosial') {
                $this->validasiPaguSosial($jumlah, $tanggal);
            }

            $pengeluaran = Pengeluaran::create([
                'jenis' => $jenis,
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
                'tanggal' => $tanggal,
                'input_by' => $userId,
            ]);

            $this->jurnalKas->catat(
                tipe: 'keluar',
                kategori: $jenis === 'koperasi' ? 'pengeluaran_koperasi' : 'pengeluaran_dana_sosial',
                kantong: 'kas_kecil',
                jumlah: $jumlah,
                keterangan: $keterangan,
                referensiId: $pengeluaran->id,
                tanggal: $tanggal,
                userId: $userId,
            );

            // Audit log untuk pencatatan pengeluaran
            $labelJenis = $jenis === 'koperasi' ? 'Pengeluaran Koperasi' : 'Pengeluaran Dana Sosial';

            AuditLog::catat(
                aksi: 'pengeluaran_dicatat',
                keterangan: "{$labelJenis} dicatat: {$keterangan}, nominal: ".number_format($jumlah, 0, ',', '.').', dari Kas Kecil',
                dataLama: null,
                dataBaru: [
                    'pengeluaran_id' => $pengeluaran->id,
                    'jenis' => $jenis,
                    'jumlah' => $jumlah,
                    'keterangan' => $keterangan,
                    'tanggal' => $tanggal,
                    'input_by' => $userId,
                    'kantong' => 'kas_kecil',
                ]
            );

            return $pengeluaran;
        });
    }

    /**
     * Pagu dana sosial bulanan = setting cadangan_sosial_bulan. Berlaku untuk
     * pengeluaran manual di halaman Pengeluaran dan klaim santunan (sama-sama
     * lewat catat()). Bulan dihitung dari tanggal pengeluaran.
     */
    private function validasiPaguSosial(float $jumlah, string $tanggal): void
    {
        $tgl = Carbon::parse($tanggal);
        $pagu = SettingKas::nilai(
            SettingKas::CADANGAN,
            (float) config('koperasi.cadangan_sosial_bulan', 5_000_000)
        );

        $terpakai = (float) Pengeluaran::where('jenis', 'dana_sosial')
            ->whereYear('tanggal', $tgl->year)
            ->whereMonth('tanggal', $tgl->month)
            ->sum('jumlah');

        $sisa = $pagu - $terpakai;

        if ($jumlah > $sisa) {
            $rupiah = fn (float $n) => 'Rp '.number_format($n, 0, ',', '.');
            throw new RuntimeException(
                "Melebihi pagu dana sosial {$tgl->translatedFormat('F Y')}: "
                ."pagu {$rupiah($pagu)}, terpakai {$rupiah($terpakai)}, sisa {$rupiah(max(0, $sisa))}."
            );
        }
    }
}
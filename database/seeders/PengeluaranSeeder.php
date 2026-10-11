<?php

namespace Database\Seeders;

use App\Services\Keuangan\PengeluaranService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengeluaranSeeder extends Seeder
{
    /**
     * Fixed cases pengeluaran Koperasi & Dana Sosial, tersebar 6 bulan terakhir.
     * Lewat PengeluaranService supaya jurnal + saldo kas konsisten otomatis.
     */
    public function run(): void
    {
        // Idempoten: skip bila sudah pernah di-seed
        if (DB::table('pengeluaran')->exists()) {
            return;
        }

        $service = app(PengeluaranService::class);
        $bendaharaId = $this->userId('BEN-000001');

        $koperasi = [
            // [keterangan, jumlah, bulan lalu]
            ['Bel ATK sekretariat (kertas, tinta, pulpen)', 350_000, 5],
            ['Bayar listrik kantor koperasi', 275_000, 5],
            ['Cetak formulir & buku tabungan anggota baru', 420_000, 4],
            ['Service printer & komputer sekretariat', 500_000, 4],
            ['Bayar air kantor', 120_000, 4],
            ['Konsumsi rapat pengurus bulanan', 300_000, 3],
            ['Transportasi penagihan angsuran cabang Samarinda', 250_000, 3],
            ['Bel perlengkapan kebersihan kantor', 150_000, 3],
            ['Langganan internet sekretariat (3 bulan)', 900_000, 2],
            ['Honor admin sistem (pemeliharaan aplikasi)', 500_000, 2],
            ['Pembelian map arsip & lemari dokumen', 750_000, 1],
            ['Bayar listrik & air kantor', 395_000, 1],
            ['Konsumsi sosialisasi program koperasi', 450_000, 0],
            ['Cetak sertifikat anggota & kartu anggota', 380_000, 0],
            ['Biaya pengiriman dokumen & logistik', 180_000, 0],
        ];

        $danaSosial = [
            // [keterangan, jumlah, bulan lalu, tipe]
            ['Bantuan biaya pengobatan anggota sakit (TOP-100007)', 750_000, 5, 'santunan'],
            ['Santunan duka keluarga anggota wafat', 1_000_000, 5, 'santunan'],
            ['Bantuan bencana banjir rumah anggota', 850_000, 4, 'santunan'],
            ['Santunan kematian anggota (TOP-100016)', 1_000_000, 3, 'santunan'],
            ['Bantuan biaya sekolah anak anggota kurang mampu', 600_000, 3, 'santunan'],
            ['Bantuan operasi anggota (TOP-100010)', 1_200_000, 2, 'santunan'],
            ['Sumbangan korban kebakaran lingkungan kerja', 700_000, 2, 'santunan'],
            ['Bantuan hari raya anggota prasejahtera', 500_000, 1, 'santunan'],
            ['Santunan persalinan anggota', 400_000, 0, 'santunan'],
            ['Bantuan pangan keluarga anggota terdampak PHK', 650_000, 0, 'santunan'],
        ];

        foreach ($koperasi as [$ket, $jumlah, $bulanLalu]) {
            $service->catat('koperasi', $jumlah, $ket, $this->tanggal($bulanLalu), $bendaharaId);
        }

        foreach ($danaSosial as [$ket, $jumlah, $bulanLalu, $tipe]) {
            $service->catat('dana_sosial', $jumlah, $ket, $this->tanggal($bulanLalu), $bendaharaId, $tipe);
        }
    }

    /** Tanggal di tengah bulan (tanggal 10) agar realistis & stabil. */
    private function tanggal(int $bulanLalu): string
    {
        return now()->subMonths($bulanLalu)->day(10)->toDateString();
    }

    private function userId(string $noKaryawan): ?int
    {
        return DB::table('users')->where('no_karyawan', $noKaryawan)->value('id');
    }
}

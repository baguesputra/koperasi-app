<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimpananSeeder extends Seeder
{
    public function run(): void
    {
        $nominalWajib = (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 0);
        $nominalDanaSosial = (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 0);
        $bendaharaId = User::where('no_karyawan', 'BEN-000001')->value('id');

        // Beberapa anggota sengaja belum setor bulan ini -> tetap ada data "belum simpanan"
        $skipBulanIni = ['TOP-100005', 'TOP-100010', 'TOP-100015', 'TOP-100020', 'TOP-100025'];

        $anggotaList = Anggota::where('status', 'aktif')->get();

        foreach ($anggotaList as $anggota) {
            for ($m = 5; $m >= 0; $m--) {
                $bulan = now()->subMonths($m);
                $periode = $bulan->format('Y-m');

                if ($m === 0 && in_array($anggota->no_karyawan, $skipBulanIni)) {
                    continue;
                }

                // Belum jadi anggota di bulan tsb
                if ($anggota->tanggal_jadi_anggota->format('Y-m') > $periode) {
                    continue;
                }

                $tanggalInput = $bulan->day(25);
                if ($tanggalInput->gt(now())) {
                    $tanggalInput = now();
                }

                $this->catatSimpanan($anggota->id, 'wajib', $nominalWajib, $periode, $tanggalInput, $bendaharaId);
                $this->catatSimpanan($anggota->id, 'dana_sosial', $nominalDanaSosial, $periode, $tanggalInput, $bendaharaId);
            }
        }
    }

    private function catatSimpanan(int $anggotaId, string $jenis, float $jumlah, string $periode, $tanggalInput, ?int $inputBy): void
    {
        Simpanan::firstOrCreate(
            ['anggota_id' => $anggotaId, 'jenis' => $jenis, 'bulan_periode' => $periode],
            [
                'jumlah' => $jumlah,
                'tanggal_input' => $tanggalInput,
                'input_by' => $inputBy,
            ]
        );
    }
}
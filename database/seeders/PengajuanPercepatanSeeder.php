<?php

namespace Database\Seeders;

use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengajuanPercepatanSeeder extends Seeder
{
    /**
     * Fixed cases perubahan tenor/pelunasan:
     *   A. diajukan           — menunggu verifikasi Bendahara
     *   B. approved_bendahara — menunggu Ketua
     *   C. aktif (perpanjang) — jadwal lama digantikan jadwal baru
     *   D. aktif (lunas_total)— satu cicilan pelunasan final
     *   E. ditolak            — tidak mengubah angsuran
     *
     * Idempoten: skip bila sudah ada data.
     */
    public function run(): void
    {
        if (PengajuanPercepatan::exists()) {
            return;
        }

        $bendaharaId = DB::table('users')->where('no_karyawan', 'BEN-000001')->value('id');
        $ketuaId = DB::table('users')->where('no_karyawan', 'KET-000001')->value('id');

        // ---------- A. DIAJUKAN: perpanjang di TOP-100007 (aktif 1jt/3bln, lunas 1) ----------
        $pA = $this->pinjamanByKaryawan('TOP-100007');
        PengajuanPercepatan::firstOrCreate(
            ['pinjaman_id' => $pA->id, 'tipe' => 'perpanjang', 'status' => 'diajukan'],
            [
                'tenor_lama' => 3, 'tenor_baru' => 6,
                'keterangan' => 'Beban bulan ini berat, mohon perpanjang sisa tenor.',
                'tanggal_pengajuan' => now()->subDays(2),
            ]
        );

        // ---------- B. APPROVED_BENDAHARA: percepat di TOP-100017 (aktif 2jt/4bln, lunas 2) ----------
        $pB = $this->pinjamanByKaryawan('TOP-100017');
        PengajuanPercepatan::firstOrCreate(
            ['pinjaman_id' => $pB->id, 'tipe' => 'percepat', 'status' => 'approved_bendahara'],
            [
                'tenor_lama' => 4, 'tenor_baru' => 2,
                'keterangan' => 'Ada bonus tahunan, ingin melunasi lebih cepat.',
                'catatan_bendahara' => 'Riwayat angsuran lancar, layak diteruskan.',
                'tanggal_pengajuan' => now()->subDays(4),
            ]
        );

        // ---------- C. AKTIF (perpanjang): TOP-100010 (aktif 3jt/6bln, lunas 3) ----------
        $pC = $this->pinjamanByKaryawan('TOP-100010');
        $sisaC = $this->sisaPokok($pC); // sisa pokok 3 cicilan terakhir
        $percepatanC = PengajuanPercepatan::firstOrCreate(
            ['pinjaman_id' => $pC->id, 'tipe' => 'perpanjang', 'status' => 'aktif'],
            [
                'tenor_lama' => 6, 'tenor_baru' => 5,
                'sisa_pokok_saat_approval' => $sisaC,
                'keterangan' => 'Perpanjang agar cicilan bulanan lebih ringan.',
                'catatan_bendahara' => 'Verifikasi kemampuan bayar OK.',
                'catatan_ketua' => 'Disetujui, berlaku bulan depan.',
                'bulan_berlaku' => 'bulan_depan',
                'tanggal_pengajuan' => now()->subDays(20),
            ]
        );
        $this->terapkanPerpanjang($percepatanC, $pC, 5, 'bulan_depan', $ketuaId);

        // ---------- D. AKTIF (lunas_total): pinjaman kecil khusus skenario ----------
        $pD = $this->buatPinjamanKecil('TOP-100011');
        $sisaD = $this->sisaPokok($pD);
        $bungaD = round($sisaD * 0.01, 2);
        $percepatanD = PengajuanPercepatan::firstOrCreate(
            ['pinjaman_id' => $pD->id, 'tipe' => 'lunas_total', 'status' => 'aktif'],
            [
                'tenor_lama' => $pD->tenor_bulan, 'tenor_baru' => null,
                'sisa_pokok_saat_approval' => $sisaD,
                'nominal_final' => $sisaD + $bungaD,
                'keterangan' => 'Menerima warisan, minta lunas total sekarang.',
                'catatan_bendahara' => 'Tidak ada masalah.',
                'catatan_ketua' => 'Setuju, hitung pelunasan sesuai ketentuan.',
                'bulan_berlaku' => 'bulan_ini',
                'tanggal_pengajuan' => now()->subDays(15),
            ]
        );
        $this->terapkanLunasTotal($percepatanD, $pD, 'bulan_ini');

        // ---------- E. DITOLAK: dua kasus ----------
        $pE1 = $this->pinjamanByKaryawan('TOP-100028'); // pinjaman approved_bendahara miliknya — pakai pinjaman aktif lain bila ada
        PengajuanPercepatan::firstOrCreate(
            ['pinjaman_id' => $pE1->id, 'tipe' => 'lunas_total', 'status' => 'ditolak'],
            [
                'tenor_lama' => 4,
                'keterangan' => 'Ingin lunas sekaligus bulan ini.',
                'catatan_bendahara' => 'Diajukan ke Ketua.',
                'catatan_ketua' => 'Ditolak, tunggu periode gaji berikutnya.',
                'tanggal_pengajuan' => now()->subDays(25),
            ]
        );

        $pE2 = $this->pinjamanByKaryawan('TOP-100008');
        PengajuanPercepatan::firstOrCreate(
            ['pinjaman_id' => $pE2->id, 'tipe' => 'perpanjang', 'status' => 'ditolak'],
            [
                'tenor_lama' => 9, 'tenor_baru' => 12,
                'keterangan' => 'Mohon perpanjang karena biaya pengobatan berlanjut.',
                'catatan_bendahara' => 'Dokumen pendukung kurang lengkap.',
                'catatan_ketua' => 'Ditolak, silakan ajukan ulang dengan surat keterangan RS.',
                'tanggal_pengajuan' => now()->subDays(30),
            ]
        );
    }

    // ---------- helper ----------

    private function pinjamanByKaryawan(string $noKaryawan): ?Pinjaman
    {
        return Pinjaman::whereHas('anggota', fn ($q) => $q->where('no_karyawan', $noKaryawan))
            ->whereIn('status', ['aktif', 'approved_bendahara'])
            ->latest('id')
            ->first();
    }

    private function sisaPokok(Pinjaman $pinjaman): float
    {
        return (float) $pinjaman->angsuran()->where('status', 'belum_bayar')->sum('nominal_pokok');
    }

    /** Tandai angsuran lama "digantikan" + buat jadwal baru (meniru approveKetua). */
    private function terapkanPerpanjang(PengajuanPercepatan $pengajuan, Pinjaman $pinjaman, int $tenorBaru, string $bulanBerlaku, int $ketuaId): void
    {
        $belumBayar = $pinjaman->angsuran()->where('status', 'belum_bayar')->orderBy('cicilan_ke')->get();

        foreach ($belumBayar as $angsuran) {
            $angsuran->update(['status' => 'digantikan', 'pengajuan_percepatan_id' => $pengajuan->id]);
        }

        $pinjaman->update(['sudah_pakai_percepatan' => true]);

        $mulai = $bulanBerlaku === 'bulan_ini' ? now() : now()->addMonthNoOverflow();
        $persentase = (float) $pinjaman->persentase_bunga / 100;
        $sisa = (float) $pengajuan->sisa_pokok_saat_approval;
        $pokokPerBulan = round($sisa / $tenorBaru, 2);

        for ($i = 1; $i <= $tenorBaru; $i++) {
            $pokok = ($i === $tenorBaru) ? $sisa : $pokokPerBulan;
            $bunga = round($sisa * $persentase, 2);

            AngsuranPercepatan::create([
                'pengajuan_percepatan_id' => $pengajuan->id,
                'cicilan_ke' => $i,
                'nominal_pokok' => $pokok,
                'nominal_bunga' => $bunga,
                'total_bayar' => $pokok + $bunga,
                'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => $mulai->copy()->addMonths($i - 1)->endOfMonth(),
            ]);

            $sisa -= $pokok;
        }

        // Cicilan pertama jadwal baru dikonfirmasi lunas bila berlaku bulan ini (simulasi sudah dibayar)
        // ponytail: sengaja dibiarkan semua belum_bayar supaya halaman konfirmasi angsuran punya data percepatan aktif
    }

    private function terapkanLunasTotal(PengajuanPercepatan $pengajuan, Pinjaman $pinjaman, string $bulanBerlaku): void
    {
        $belumBayar = $pinjaman->angsuran()->where('status', 'belum_bayar')->get();
        foreach ($belumBayar as $angsuran) {
            $angsuran->update(['status' => 'digantikan', 'pengajuan_percepatan_id' => $pengajuan->id]);
        }
        $pinjaman->update(['sudah_pakai_percepatan' => true]);

        $mulai = $bulanBerlaku === 'bulan_ini' ? now() : now()->addMonthNoOverflow();
        $persentase = (float) $pinjaman->persentase_bunga / 100;
        $sisa = (float) $pengajuan->sisa_pokok_saat_approval;
        $bunga = round($sisa * $persentase, 2);

        AngsuranPercepatan::create([
            'pengajuan_percepatan_id' => $pengajuan->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => $sisa,
            'nominal_bunga' => $bunga,
            'total_bayar' => $sisa + $bunga,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => $mulai->copy()->endOfMonth(),
        ]);
    }

    /** Pinjaman aktif kecil utk skenario lunas_total (member tanpa pinjaman lain). */
    private function buatPinjamanKecil(string $noKaryawan): Pinjaman
    {
        $pinjamanLama = Pinjaman::whereHas('anggota', fn ($q) => $q->where('no_karyawan', $noKaryawan))
            ->where('status', 'aktif')->first();

        if ($pinjamanLama) {
            return $pinjamanLama;
        }

        $anggota = \App\Models\Anggota::where('no_karyawan', $noKaryawan)->firstOrFail();
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_200_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Renovasi dapur',
            'snapshot_bank' => 'BCA',
            'snapshot_no_rekening' => '1234006006',
            'snapshot_atas_nama' => $anggota->nama,
            'persentase_bunga' => 1.00,
            'sudah_pakai_privilege_reloan' => false,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(3),
            'tanggal_pencairan' => now()->subMonths(3)->addDays(2),
        ]);

        $pokokPerBulan = 300_000;
        $sisa = 1_200_000;
        foreach (range(1, 4) as $ke) {
            $bunga = round($sisa * 0.01, 2);
            $pinjaman->angsuran()->create([
                'cicilan_ke' => $ke,
                'nominal_pokok' => $pokokPerBulan,
                'nominal_bunga' => $bunga,
                'total_bayar' => $pokokPerBulan + $bunga,
                'status' => $ke <= 1 ? 'lunas' : 'belum_bayar', // 1 dari 4 sudah lunas
                'tanggal_jatuh_tempo' => $pinjaman->tanggal_pencairan->copy()->addMonths($ke),
                'tanggal_konfirmasi_bayar' => $ke === 1 ? $pinjaman->tanggal_pencairan->copy()->addMonths($ke) : null,
                'confirmed_by' => $ke === 1 ? DB::table('users')->where('no_karyawan', 'BEN-000001')->value('id') : null,
            ]);
            $sisa -= $pokokPerBulan;
        }

        return $pinjaman;
    }
}

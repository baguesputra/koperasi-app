<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditLogSeeder extends Seeder
{
    /**
     * Fixed cases jejak audit: pengaturan, anggota, pinjaman, limit, pengeluaran.
     * Idempoten: skip bila sudah ada data.
     */
    public function run(): void
    {
        if (AuditLog::exists()) {
            return;
        }

        $admin = $this->userId('ADM-000001');
        $bendahara = $this->userId('BEN-000001');
        $ketua = $this->userId('KET-000001');

        $entries = [
            // Pengaturan bunga & limit (admin)
            [$admin, 'update_persentase_bunga', 'Persentase bunga diubah dari 1.5% menjadi 1%', ['persentase' => 1.5], ['persentase' => 1.0], 150],
            [$admin, 'update_limit_kategori', 'Limit kategori 1-3 tahun diubah dari 4jt menjadi 5jt', ['limit_maksimal' => 4_000_000], ['limit_maksimal' => 5_000_000], 140],
            [$admin, 'update_nominal_simpanan', 'Nominal simpanan wajib diubah dari 40rb menjadi 45rb', ['nominal' => 40_000], ['nominal' => 45_000], 130],
            [$admin, 'tambah_tenor', 'Tenor baru: nominal 2-3jt maksimal 6 bulan', null, ['nominal_min' => 2_000_001, 'nominal_max' => 3_000_000, 'tenor_maksimal_bulan' => 6], 120],

            // Anggota
            [$bendahara, 'create_anggota', 'Anggota baru Budi Santoso (ANG-2026-0001) didaftarkan', null, ['no_anggota' => 'ANG-2026-0001', 'nama' => 'Budi Santoso'], 100],
            [$bendahara, 'update_anggota', 'Data kontak Siti Aminah diperbarui', ['no_hp' => null], ['no_hp' => '081234567001'], 95],
            [$bendahara, 'resign_anggota', 'Agus Wijaya resign — simpanan dikembalikan via kantong pengembalian', ['status' => 'aktif'], ['status' => 'resign'], 60],

            // Pinjaman
            [$ketua, 'approve_pinjaman_ketua', "Pinjaman #3 Ahmad Ridwan disetujui Ketua & dicairkan", ['status' => 'approved_bendahara'], ['status' => 'aktif'], 80],
            [$ketua, 'reject_pinjaman_ketua', 'Pinjaman #7 ditolak Ketua dengan catatan', null, ['status' => 'ditolak'], 70],
            [$bendahara, 'approve_pinjaman_bendahara', 'Pinjaman #8 Maya Sari disetujui Bendahara, diteruskan ke Ketua', ['status' => 'diajukan'], ['status' => 'approved_bendahara'], 65],

            // Limit
            [$ketua, 'setujui_pengajuan_limit', 'Limit khusus Indah Permata disetujui menjadi Rp 7.500.000', ['limit_custom' => null], ['limit_custom' => 7_500_000], 55],
            [$ketua, 'tolak_pengajuan_limit', 'Pengajuan limit Galih Prakoso ditolak', null, ['status' => 'ditolak'], 50],

            // Percepatan
            [$ketua, 'approve_perubahan_tenor', 'Perpanjangan tenor pinjaman #10 Joko Susanto disetujui berlaku bulan depan', null, ['tenor_baru' => 6, 'bulan_berlaku' => 'bulan_depan'], 45],
            [$bendahara, 'approve_perubahan_tenor_bendahara', 'Perubahan tenor #12 disetujui Bendahara, diteruskan ke Ketua', ['status' => 'diajukan'], ['status' => 'approved_bendahara'], 42],

            // Simpanan & angsuran
            [$bendahara, 'konfirmasi_simpanan_wajib', 'Simpanan wajib 25 anggota dikonfirmasi lunas periode bulan lalu', null, ['jumlah_anggota' => 25], 35],
            [$bendahara, 'konfirmasi_angsuran', 'Konfirmasi 6 angsuran lunas massal', null, ['jumlah_angsuran' => 6], 33],

            // Pengeluaran
            [$bendahara, 'catat_pengeluaran_koperasi', 'Pengeluaran koperasi: Bel ATK sekretariat Rp 350.000', null, ['jenis' => 'koperasi', 'jumlah' => 350_000], 30],
            [$bendahara, 'catat_pengeluaran_dana_sosial', 'Pengeluaran dana sosial: Santunan duka Rp 1.000.000', null, ['jenis' => 'dana_sosial', 'jumlah' => 1_000_000], 28],

            // Topup kas
            [$admin, 'topup_saldo_kas', 'Topup saldo Dana Pinjaman Rp 20.000.000', null, ['kantong' => 'pinjaman', 'jumlah' => 20_000_000], 20],
        ];

        foreach ($entries as [$userId, $aksi, $keterangan, $lama, $baru, $hariLalu]) {
            $log = new AuditLog([
                'user_id' => $userId,
                'aksi' => $aksi,
                'keterangan' => $keterangan,
                'data_lama' => $lama,
                'data_baru' => $baru,
            ]);
            $log->created_at = now()->subDays($hariLalu)->setHour(9)->setMinute(rand(10, 50));
            $log->updated_at = $log->created_at;
            $log->save();
        }
    }

    private function userId(string $noKaryawan): ?int
    {
        return DB::table('users')->where('no_karyawan', $noKaryawan)->value('id');
    }
}

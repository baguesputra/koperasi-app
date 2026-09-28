<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\PengajuanLimit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengajuanLimitSeeder extends Seeder
{
    public function run(): void
    {
        if (PengajuanLimit::exists()) {
            return;
        }

        $ketuaId = DB::table('users')->where('no_karyawan', 'KET-000001')->value('id');

        // 1. DIJAKAN: Siti Aminah (limit kategori 1-3 thn = 5jt) minta 8jt
        $a1 = Anggota::where('no_karyawan', 'TOP-100002')->first();
        PengajuanLimit::firstOrCreate(
            ['anggota_id' => $a1->id, 'status' => 'diajukan'],
            [
                'limit_saat_ini' => 5_000_000,
                'limit_diminta' => 8_000_000,
                'keterangan' => 'Butuh modal tambahan buka toko aksesoris HP.',
                'tanggal_pengajuan' => now()->subDays(5),
            ]
        );

        // 2. DIJAKAN: Dewi Lestari (kategori >5 thn = 10jt, tapi sudah pakai reloan → limit saat ini 10jt)
        // Dia minta naik ke 15jt (di luar kategori, test edge)
        $a2 = Anggota::where('no_karyawan', 'TOP-100004')->first();
        PengajuanLimit::firstOrCreate(
            ['anggota_id' => $a2->id, 'status' => 'diajukan'],
            [
                'limit_saat_ini' => 10_000_000,
                'limit_diminta' => 12_000_000,
                'keterangan' => 'Butuh modal ekspansi usaha kuliner rumahan.',
                'tanggal_pengajuan' => now()->subDays(3),
            ]
        );

        // 3. APPROVED_BENDAHARA: anggota bulk TOP-100009 (3-5 thn = 7jt) minta 10jt, bendahara usul 9jt
        $a3 = Anggota::where('no_karyawan', 'TOP-100009')->first();
        PengajuanLimit::firstOrCreate(
            ['anggota_id' => $a3->id, 'status' => 'approved_bendahara'],
            [
                'limit_saat_ini' => 7_000_000,
                'limit_diminta' => 10_000_000,
                'limit_disetujui_bendahara' => 9_000_000,
                'keterangan' => 'Perlu tambahan modal usaha',
                'catatan_bendahara' => 'Diverifikasi, usul 9jt ke Ketua.',
                'tanggal_pengajuan' => now()->subDays(2),
            ]
        );

        // 4. DISETUJUI: Anggota bulk TOP-100015 (1-3 thn = 5jt) minta 8jt, bendahara 7.5jt, final 8jt
        $a4 = Anggota::where('no_karyawan', 'TOP-100015')->first();
        $p4 = PengajuanLimit::firstOrCreate(
            ['anggota_id' => $a4->id, 'status' => 'disetujui'],
            [
                'limit_saat_ini' => 5_000_000,
                'limit_diminta' => 8_000_000,
                'limit_disetujui_bendahara' => 7_500_000,
                'limit_disetujui' => 8_000_000,
                'keterangan' => 'Modal usaha makanan ringan.',
                'catatan_bendahara' => 'Diverifikasi, usul 7.5jt.',
                'catatan_ketua' => 'Disetujui, usaha berpotensi.',
                'tanggal_pengajuan' => now()->subDays(15),
            ]
        );
        // Update limit_custom anggota
        $p4->anggota->update(['limit_custom' => 8_000_000]);

        // 5. DISETUJUI: anggota bulk TOP-100025 (>=5 thn = 10jt) minta 12jt, bendahara 11jt, final 12jt
        $a5 = Anggota::where('no_karyawan', 'TOP-100025')->first();
        $p5 = PengajuanLimit::firstOrCreate(
            ['anggota_id' => $a5->id, 'status' => 'disetujui'],
            [
                'limit_saat_ini' => 10_000_000,
                'limit_diminta' => 12_000_000,
                'limit_disetujui_bendahara' => 11_000_000,
                'limit_disetujui' => 12_000_000,
                'keterangan' => 'Perlu modal untuk ekspansi kios.',
                'catatan_bendahara' => 'Diverifikasi, usul 11jt.',
                'catatan_ketua' => 'Disetujui, limit 12jt.',
                'tanggal_pengajuan' => now()->subDays(20),
            ]
        );
        $p5->anggota->update(['limit_custom' => 12_000_000]);

        // 6. DITOLAK BENDAHARA (final): TOP-100030 minta 10jt
        $a6 = Anggota::where('no_karyawan', 'TOP-100030')->first();
        PengajuanLimit::firstOrCreate(
            ['anggota_id' => $a6->id, 'status' => 'ditolak'],
            [
                'limit_saat_ini' => 5_000_000,
                'limit_diminta' => 10_000_000,
                'keterangan' => 'Minta naik limit besar.',
                'catatan_bendahara' => 'Ditolak, riwayat angsuran terlalu singkat untuk lonjakan begitu besar.',
                'tanggal_pengajuan' => now()->subDays(10),
            ]
        );

        // 7. DITOLAK KETUA: TOP-100040 minta 15jt, bendahara usul 12jt
        $a7 = Anggota::where('no_karyawan', 'TOP-100040')->first();
        PengajuanLimit::firstOrCreate(
            ['anggota_id' => $a7->id, 'status' => 'ditolak'],
            [
                'limit_saat_ini' => 10_000_000,
                'limit_diminta' => 15_000_000,
                'limit_disetujui_bendahara' => 12_000_000,
                'keterangan' => 'Butuh modal besar.',
                'catatan_bendahara' => 'Diverifikasi, usul 12jt.',
                'catatan_ketua' => 'Ditolak, melebihi batas maksimal kategori.',
                'tanggal_pengajuan' => now()->subDays(8),
            ]
        );

        // 8. DISETUJUI: Bendahara BEN-000001 punya anggota? tidak — hanya members. Lewati.
        // Tambah 1: Anggota bulk TOP-100035 (1-3 thn = 5jt) disetujui → 7jt
        $a8 = Anggota::where('no_karyawan', 'TOP-100035')->first();
        $p8 = PengajuanLimit::firstOrCreate(
            ['anggota_id' => $a8->id, 'status' => 'disetujui'],
            [
                'limit_saat_ini' => 5_000_000,
                'limit_diminta' => 7_000_000,
                'keterangan' => 'Modal usaha laundry.',
                'catatan_ketua' => 'Disetujui, limit 7jt.',
                'tanggal_pengajuan' => now()->subDays(12),
            ]
        );
        $p8->anggota->update(['limit_custom' => 7_000_000]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\RekeningTabungan;
use App\Models\Simpanan;
use App\Models\TabunganTransaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pengurus_dapat_melihat_dashboard_dengan_saldo_bank(): void
    {
        $kas = KasKoperasi::first();
        $kas->update([
            'saldo_bank' => 25_000_000,
            'saldo_kas_kecil' => 5_000_000,
        ]);

        $this->masuk('ADM-000001');

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('stats.saldo_bank')
                ->where('stats.saldo_bank', 25_000_000)
                ->has('stats.saldo_kas_kecil')
                ->where('stats.saldo_kas_kecil', 5_000_000)
                ->has('stats.saldo_dana_sosial')
                ->has('actionable.penghapusan_pinjaman_menunggu')
                ->has('actionable.penghapusan_pinjaman_berjalan')
                ->missing('stats.total_keseluruhan')
                ->missing('stats.pendapatan_bunga_bulan_ini')
            );
    }

    public function test_dashboard_menghitung_saldo_dana_sosial_live_dari_jurnal_kas(): void
    {
        JurnalKas::where('kantong', 'dana_sosial')->delete();

        $kas = KasKoperasi::first();
        $kas->update(['saldo_dana_sosial' => 1_000_000]);

        $user = $this->masuk('ADM-000001');

        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'dana_sosial_bulanan',
            'kantong' => 'dana_sosial',
            'jumlah' => 2_500_000,
            'saldo_setelah' => 2_500_000,
            'keterangan' => 'Iuran dana sosial aktif',
            'tanggal' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        JurnalKas::create([
            'tipe' => 'keluar',
            'kategori' => 'pengeluaran_dana_sosial',
            'kantong' => 'dana_sosial',
            'jumlah' => 400_000,
            'saldo_setelah' => 2_100_000,
            'keterangan' => 'Santunan aktif',
            'tanggal' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $dibatalkan = JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'dana_sosial_bulanan',
            'kantong' => 'dana_sosial',
            'jumlah' => 900_000,
            'saldo_setelah' => 3_000_000,
            'keterangan' => 'Iuran dibatalkan',
            'tanggal' => now()->toDateString(),
            'created_by' => $user->id,
            'dibatalkan_pada' => now(),
            'dibatalkan_oleh' => $user->id,
            'alasan_batal' => 'Uji storno',
        ]);

        JurnalKas::create([
            'tipe' => 'keluar',
            'kategori' => 'dana_sosial_bulanan',
            'kantong' => 'dana_sosial',
            'jumlah' => 900_000,
            'saldo_setelah' => 2_100_000,
            'keterangan' => '[PEMBALIK] Uji storno',
            'tanggal' => now()->toDateString(),
            'created_by' => $user->id,
            'koreksi_dari_id' => $dibatalkan->id,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.saldo_dana_sosial', 2_100_000)
            );
    }

    public function test_anggota_biasa_dialihkan_ke_portal_dashboard(): void
    {
        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('portal.dashboard'));
    }

    public function test_grafik_arus_kas_dan_tren_12_bulan_mencakup_tabungan_pengeluaran_dan_mengecualikan_transfer_internal(): void
    {
        TabunganTransaksi::query()->delete();
        RekeningTabungan::query()->delete();
        JurnalKas::query()->delete();
        Pinjaman::query()->delete();
        Simpanan::query()->delete();

        cache()->forget('dashboard_grafik_tren_12m');
        cache()->forget('dashboard_grafik_kas_12m');

        $ben = $this->masuk('BEN-000001');
        $anggota = $this->buatAnggota('TAB-900099');
        $tanggal = now()->toDateString();
        $periode = now()->format('Y-m');

        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'pokok',
            'jumlah' => 500_000,
            'bulan_periode' => $periode,
            'tanggal_input' => $tanggal,
            'input_by' => $ben->id,
        ]);

        // Dana sosial tidak boleh tercampur ke seri Simpanan (Pokok/Wajib) di grafik tren
        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'dana_sosial',
            'jumlah' => 100_000,
            'bulan_periode' => $periode,
            'tanggal_input' => $tanggal,
            'input_by' => $ben->id,
        ]);

        Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 6,
            'persentase_bunga' => 1.5,
            'status' => 'aktif',
            'tanggal_pengajuan' => $tanggal,
            'tanggal_pencairan' => $tanggal,
        ]);

        $rekening = RekeningTabungan::create([
            'anggota_id' => $anggota->id,
            'no_rekening' => 'TAB-2026-0777',
            'status' => 'aktif',
            'saldo' => 600_000,
            'suku_bunga_saat_buka' => 2.00,
            'setuju_syarat' => true,
            'tanggal_pengajuan' => $tanggal,
            'tanggal_buka' => $tanggal,
        ]);

        TabunganTransaksi::create([
            'rekening_tabungan_id' => $rekening->id,
            'anggota_id' => $anggota->id,
            'no_bukti' => '001/TEST/TAB',
            'jenis' => 'setor',
            'kanal' => 'tunai',
            'nominal' => 800_000,
            'saldo_sebelum' => 0,
            'saldo_sesudah' => 800_000,
            'suku_bunga_snapshot' => 2.00,
            'tanggal' => $tanggal,
            'keterangan' => 'Setoran uji grafik',
            'created_by' => $ben->id,
        ]);

        TabunganTransaksi::create([
            'rekening_tabungan_id' => $rekening->id,
            'anggota_id' => $anggota->id,
            'no_bukti' => '002/TEST/TAB',
            'jenis' => 'tarik',
            'kanal' => 'tunai',
            'nominal' => 200_000,
            'saldo_sebelum' => 800_000,
            'saldo_sesudah' => 600_000,
            'suku_bunga_snapshot' => 2.00,
            'tanggal' => $tanggal,
            'keterangan' => 'Penarikan uji grafik',
            'created_by' => $ben->id,
        ]);

        $kasRows = [
            ['tipe' => 'masuk', 'kategori' => 'pembayaran_angsuran', 'kantong' => 'bank', 'jumlah' => 1_200_000],
            ['tipe' => 'masuk', 'kategori' => 'setor_tabungan', 'kantong' => 'tabungan', 'jumlah' => 800_000],
            ['tipe' => 'keluar', 'kategori' => 'pengeluaran_koperasi', 'kantong' => 'kas_kecil', 'jumlah' => 300_000],
            ['tipe' => 'keluar', 'kategori' => 'tarik_tabungan', 'kantong' => 'tabungan', 'jumlah' => 200_000],
            // Transfer internal antar kas tidak boleh mendistorsi arus kas masuk/keluar operasional
            ['tipe' => 'keluar', 'kategori' => 'sisih_kas_kecil', 'kantong' => 'bank', 'jumlah' => 5_000_000],
            ['tipe' => 'masuk', 'kategori' => 'terima_sisih_kas_kecil', 'kantong' => 'kas_kecil', 'jumlah' => 5_000_000],
        ];

        foreach ($kasRows as $idx => $row) {
            JurnalKas::create([
                ...$row,
                'saldo_setelah' => 0,
                'keterangan' => "Mutasi uji {$idx}",
                'tanggal' => $tanggal,
                'created_by' => $ben->id,
            ]);
        }

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('grafikKas', 12)
                ->where('grafikKas.11.masuk', 2_000_000)
                ->where('grafikKas.11.keluar', 500_000)
                ->where('grafikKas.11.net', 1_500_000)
                ->where('grafikKas.11.angsuran', 1_200_000)
                ->where('grafikKas.11.setor_tabungan', 800_000)
                ->where('grafikKas.11.pengeluaran', 300_000)
                ->where('grafikKas.11.tarik_tabungan', 200_000)
                ->has('grafikTren', 12)
                ->where('grafikTren.11.simpanan', 500_000)
                ->where('grafikTren.11.tabungan', 800_000)
                ->where('grafikTren.11.tabungan_tarik', 200_000)
                ->where('grafikTren.11.pinjaman', 2_000_000)
                ->where('grafikTren.11.pinjaman_count', 1)
                ->where('aktivitasTerbaru', fn ($items) => collect($items)->contains('tipe', 'tabungan'))
            );
    }
}

<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AngsuranKonfirmasiTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // Pastikan role 'anggota' ter-refresh di transaction ini
        if (! Role::where('name', 'anggota')->exists()) {
            Role::firstOrCreate(['name' => 'anggota', 'guard_name' => 'web']);
        }
    }

    private function buatPinjamanAktifDenganAngsuran(int $tenor = 3): Pinjaman
    {
        $anggota = $this->buatAnggota();
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 900_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Uji',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(2),
            'tanggal_pencairan' => now()->subMonths(2),
        ]);

        foreach ([1 => [300_000, 9_000], 2 => [300_000, 6_000], 3 => [300_000, 3_000]] as $ke => [$pokok, $bunga]) {
            $pinjaman->angsuran()->create([
                'cicilan_ke' => $ke, 'nominal_pokok' => $pokok, 'nominal_bunga' => $bunga,
                'total_bayar' => $pokok + $bunga, 'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->subMonths(2 - ($ke - 1))->endOfMonth(),
            ]);
        }

        return $pinjaman;
    }

    public function test_konfirmasi_satuan_mencatat_jurnal_dan_lunas(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        $angsuran = $pinjaman->angsuran()->where('cicilan_ke', 1)->first();

        $this->masuk('BEN-000001');
        $saldoSebelum = (float) KasKoperasi::first()->saldo_pinjaman;

        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran->id],
        ])->assertStatus(302);

        $this->assertDatabaseHas('angsuran', ['id' => $angsuran->id, 'status' => 'lunas']);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'pembayaran_angsuran', 'tipe' => 'masuk',
            'jumlah' => 309_000, 'referensi_id' => $angsuran->id,
        ]);
        $this->assertEquals($saldoSebelum + 309_000, (float) KasKoperasi::first()->saldo_pinjaman);
    }

    public function test_pinjaman_otomatis_lunas_saats_semua_cicilan_selesai(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        $ids = $pinjaman->angsuran()->pluck('id')->map(fn ($id) => 'n-'.$id)->all();

        $jurnalSebelum = (int) (JurnalKas::max('id') ?? 0);
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.angsuran.konfirmasi'), ['angsuran_ids' => $ids])->assertStatus(302);

        $this->assertSame('lunas', $pinjaman->refresh()->status);
        // ponytail: referensi_id polimorfik tanpa kolom tipe — scope via id jurnal baru agar bebas tabrakan data seeder
        $totalBayar = JurnalKas::where('kategori', 'pembayaran_angsuran')
            ->where('id', '>', $jurnalSebelum)->sum('jumlah');
        $this->assertEquals(918_000, (float) $totalBayar);
    }

    public function test_index_menandai_angsuran_dengan_pengajuan_percepatan_berjalan(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        PengajuanPercepatan::create([
            'pinjaman_id' => $pinjaman->id, 'tipe' => 'perpanjang',
            'tenor_lama' => 3, 'tenor_baru' => 5, 'keterangan' => 'Berat bulan ini',
            'status' => 'diajukan', 'tanggal_pengajuan' => now(),
        ]);

        $this->masuk('BEN-000001');
        $res = $this->get(route('bendahara.angsuran.index'));

        $res->assertOk()->assertInertia(fn ($page) => $page->component('Bendahara/Angsuran/Index'));

        $adaTanda = collect($res->viewData('page')['props']['daftarAngsuran'] ?? [])
            ->contains(fn ($item) => $item['ada_pengajuan_percepatan'] === true && $item['no_anggota'] === $pinjaman->anggota->no_anggota);
        $this->assertTrue($adaTanda);
    }
}

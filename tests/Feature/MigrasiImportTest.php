<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Services\Migrasi\MigrasiPinjamanService;
use App\Services\Migrasi\MigrasiSimpananService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class MigrasiImportTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_migrasi_pinjaman_membuat_jadwal_lunas_sebagian_dan_jurnal(): void
    {
        $anggota = $this->buatAnggota('TOP-900010');
        $admin = $this->masuk('ADM-000001');

        $cairSebelum = JurnalKas::where('kategori', 'pencairan_pinjaman')->count();
        $bayarSebelum = JurnalKas::where('kategori', 'pembayaran_angsuran')->count();

        $pinjaman = app(MigrasiPinjamanService::class)->proses(
            anggota: $anggota,
            tanggalPengajuan: '2024-01-10',
            tanggalPencairan: '2024-01-15',
            nominal: 10_000_000,
            tenorBulan: 10,
            persentaseBunga: 1,
            sudahBayar: 4,
            tanggalBayarTerakhir: '2024-05-15',
            userId: $admin->id,
        );

        $this->assertSame('aktif', $pinjaman->status);
        $this->assertSame(10, Pinjaman::find($pinjaman->id)->angsuran()->count());
        $this->assertSame(4, Pinjaman::find($pinjaman->id)->angsuran()->where('status', 'lunas')->count());
        $this->assertSame(6, Pinjaman::find($pinjaman->id)->sisaCicilanAktif());
        $this->assertGreaterThan(0, Pinjaman::find($pinjaman->id)->sisaTotalBayarAktif());

        $this->assertSame(1, JurnalKas::where('kategori', 'pencairan_pinjaman')->where('kantong', 'pinjaman')->count() - $cairSebelum);
        $this->assertSame(4, JurnalKas::where('kategori', 'pembayaran_angsuran')->where('kantong', 'pinjaman')->count() - $bayarSebelum);
    }

    public function test_migrasi_pinjaman_lunas_penuh_ubah_status_lunas(): void
    {
        $anggota = $this->buatAnggota('TOP-900011');
        $admin = $this->masuk('ADM-000001');

        $pinjaman = app(MigrasiPinjamanService::class)->proses(
            anggota: $anggota,
            tanggalPengajuan: '2024-01-10',
            tanggalPencairan: '2024-01-15',
            nominal: 3_000_000,
            tenorBulan: 3,
            persentaseBunga: 1,
            sudahBayar: 3,
            tanggalBayarTerakhir: '2024-04-15',
            userId: $admin->id,
        );

        $this->assertSame('lunas', $pinjaman->status);
        $this->assertSame(0, Pinjaman::find($pinjaman->id)->sisaCicilanAktif());
    }

    public function test_migrasi_pinjaman_duplikat_ditolak(): void
    {
        $anggota = $this->buatAnggota('TOP-900012');
        $admin = $this->masuk('ADM-000001');
        $service = app(MigrasiPinjamanService::class);

        $argumen = [
            'anggota' => $anggota,
            'tanggalPengajuan' => '2024-01-10',
            'tanggalPencairan' => '2024-01-15',
            'nominal' => 5_000_000,
            'tenorBulan' => 5,
            'persentaseBunga' => 1,
            'sudahBayar' => 0,
            'tanggalBayarTerakhir' => null,
            'userId' => $admin->id,
        ];

        $service->proses(...$argumen);

        $this->expectException(\RuntimeException::class);
        $service->proses(...$argumen);
    }

    public function test_migrasi_simpanan_membuat_simpanan_dan_jurnal(): void
    {
        $anggota = $this->buatAnggota('TOP-900013');
        $admin = $this->masuk('ADM-000001');

        $jurnalSebelum = JurnalKas::where('kategori', 'simpanan_wajib_masuk')->count();

        app(MigrasiSimpananService::class)->proses(
            anggota: $anggota,
            jenis: 'wajib',
            bulanPeriode: '2024-01',
            jumlah: 50_000,
            tanggalInput: '2024-01-15',
            userId: $admin->id,
        );

        $this->assertSame(1, Simpanan::where('anggota_id', $anggota->id)->where('jenis', 'wajib')->where('bulan_periode', '2024-01')->count());
        $this->assertSame(1, JurnalKas::where('kategori', 'simpanan_wajib_masuk')->where('kantong', 'simpanan')->count() - $jurnalSebelum);

        $kas = KasKoperasi::first();
        $this->assertGreaterThan(0, (float) $kas->saldo_simpanan);
    }

    public function test_migrasi_simpanan_duplikat_ditolak(): void
    {
        $anggota = $this->buatAnggota('TOP-900014');
        $admin = $this->masuk('ADM-000001');
        $service = app(MigrasiSimpananService::class);

        $service->proses($anggota, 'wajib', '2024-01', 50_000, '2024-01-15', $admin->id);

        $this->expectException(\RuntimeException::class);
        $service->proses($anggota, 'wajib', '2024-01', 50_000, '2024-01-15', $admin->id);
    }

    public function test_halaman_migrasi_hanya_untuk_permission_migrasi(): void
    {
        $this->masuk('ADM-000001');
        Permission::firstOrCreate(['name' => 'migrasi.kelola']);
        $this->get(route('migrasi.index'))->assertOk();

        $anggota = $this->buatAnggota('TOP-900015');
        $this->actingAs($anggota->user);
        $this->get(route('migrasi.index'))->assertForbidden();
    }
}

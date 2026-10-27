<?php

namespace Tests\Feature\Api;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\Pinjaman;
use App\Models\SettingSimpanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class MobilePengurusLanjutanTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function headerToken(string $token)
    {
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function tokenIzin(string $noKaryawan, array $izin): string
    {
        $user = User::factory()->create(['no_karyawan' => $noKaryawan]);
        $user->givePermissionTo(...$izin);
        auth()->forgetGuards();

        return $user->createToken('uji')->plainTextToken;
    }

    private function buatPinjamanAktif(Anggota $anggota, array $override = []): Pinjaman
    {
        $pinjaman = Pinjaman::create(array_merge([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Uji antrean lanjutan',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonth()->format('Y-m-d'),
            'tanggal_pencairan' => now()->subMonth()->format('Y-m-d'),
        ], $override));

        $jatuhTempo = now()->subMonth();
        foreach (range(1, 4) as $ke) {
            $pinjaman->angsuran()->create([
                'cicilan_ke' => $ke,
                'nominal_pokok' => 500_000,
                'nominal_bunga' => 20_000,
                'total_bayar' => 520_000,
                'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => $jatuhTempo->copy()->addMonths($ke - 1)->endOfMonth(),
            ]);
        }

        return $pinjaman;
    }

    public function test_tamu_ditolak_di_endpoint_batch6(): void
    {
        $this->getJson('/api/v1/pengurus/angsuran')->assertUnauthorized();
        $this->getJson('/api/v1/pengurus/simpanan')->assertUnauthorized();
        $this->getJson('/api/v1/pengurus/list-pinjaman')->assertUnauthorized();
        $this->getJson('/api/v1/pengurus/list-simpanan')->assertUnauthorized();
        $this->getJson('/api/v1/laporan')->assertUnauthorized();
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $this->postJson('/api/v1/pengurus/angsuran/konfirmasi', [])->assertUnauthorized();
        $this->postJson('/api/v1/pengurus/simpanan/konfirmasi', [])->assertUnauthorized();
    }

    public function test_antrean_tanpa_izin_ditolak_403(): void
    {
        $anggota = $this->buatAnggota('TOP-981001');
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)->getJson('/api/v1/pengurus/angsuran')->assertForbidden();
        $this->headerToken($token)->getJson('/api/v1/pengurus/simpanan')->assertForbidden();
        $this->headerToken($token)->getJson('/api/v1/pengurus/list-pinjaman')->assertForbidden();
        $this->headerToken($token)->getJson('/api/v1/pengurus/list-simpanan')->assertForbidden();
        $this->headerToken($token)->getJson('/api/v1/laporan')->assertForbidden();
    }

    public function test_antrean_angsuran_dan_konfirmasi_massal(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-981002');
        $pinjaman = $this->buatPinjamanAktif($anggota);
        $token = $this->tokenIzin('BEN-981002', ['angsuran.konfirmasi']);

        $res = $this->headerToken($token)->getJson('/api/v1/pengurus/angsuran')->assertOk();
        $milikUji = collect($res->json('data'))->where('pinjaman_id', $pinjaman->id)->values();
        $this->assertCount(4, $milikUji);
        $this->assertTrue($milikUji->every(fn ($row) => $row['jenis'] === 'normal'));
        $ids = $milikUji->pluck('kunci')->all();
        $res->assertJsonStructure(['data' => [['jenis', 'kunci', 'angsuran_id', 'pinjaman_id', 'anggota_nama', 'cicilan_ke', 'total_bayar', 'tanggal_jatuh_tempo', 'terlambat', 'diajukan_perubahan_tenor']], 'total', 'periode']);

        // Kompatibilitas: id numerik lama tetap diterima sebagai angsuran normal.
        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengurus/angsuran/konfirmasi', ['angsuran_ids' => [$milikUji->first()['angsuran_id']]])
            ->assertOk();
        $this->assertSame(1, Angsuran::where('pinjaman_id', $pinjaman->id)->where('status', 'lunas')->count());

        $target = array_slice($milikUji->where('angsuran_id', '!=', $milikUji->first()['angsuran_id'])->pluck('kunci')->all(), 0, 2);
        $resKonfirmasi = $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengurus/angsuran/konfirmasi', ['angsuran_ids' => $target])
            ->assertOk();
        $resKonfirmasi->assertJsonPath('message', '2 angsuran dikonfirmasi.');
        $this->assertEquals(1_040_000.0, (float) $resKonfirmasi->json('total'));

        $this->assertSame(3, Angsuran::where('pinjaman_id', $pinjaman->id)->where('status', 'lunas')->count());

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengurus/angsuran/konfirmasi', ['angsuran_ids' => []])
            ->assertUnprocessable();

        // Kunci tak dikenal ditolak validasi implisit: tidak ada item valid.
        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengurus/angsuran/konfirmasi', ['angsuran_ids' => ['x-99']])
            ->assertStatus(422);
    }

    public function test_antrean_simpanan_dan_konfirmasi_massal(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-981003');
        $token = $this->tokenIzin('BEN-981003', ['simpanan.konfirmasi']);

        $wajib = (float) (SettingSimpanan::where('jenis', 'wajib')->value('nominal') ?? 45_000);
        $sosial = (float) (SettingSimpanan::where('jenis', 'dana_sosial')->value('nominal') ?? 5_000);

        $res = $this->headerToken($token)->getJson('/api/v1/pengurus/simpanan')->assertOk();
        $this->assertContains($anggota->id, collect($res->json('data'))->pluck('anggota_id')->all());
        $res->assertJsonStructure(['data' => [['anggota_id', 'anggota_nama', 'jenis', 'jumlah', 'bulan_periode']], 'total', 'periode']);

        $periode = now()->format('Y-m');
        $resKonfirmasiSimpanan = $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengurus/simpanan/konfirmasi', ['anggota_ids' => [$anggota->id]])
            ->assertOk();
        $resKonfirmasiSimpanan->assertJsonPath('message', '1 anggota dikonfirmasi.');
        $this->assertEquals($wajib + $sosial, (float) $resKonfirmasiSimpanan->json('total'));

        $this->assertDatabaseHas('simpanan', ['anggota_id' => $anggota->id, 'jenis' => 'wajib', 'bulan_periode' => $periode]);
        $this->assertDatabaseHas('simpanan', ['anggota_id' => $anggota->id, 'jenis' => 'dana_sosial', 'bulan_periode' => $periode]);

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengurus/simpanan/konfirmasi', ['anggota_ids' => []])
            ->assertUnprocessable();
    }

    public function test_list_pinjaman_dengan_rekap_dan_filter(): void
    {
        $anggota = $this->buatAnggota('TOP-981004');
        $pinjaman = $this->buatPinjamanAktif($anggota);
        $token = $this->tokenIzin('ADM-981004', ['pinjaman.lihat']);

        $res = $this->headerToken($token)->getJson('/api/v1/pengurus/list-pinjaman')->assertOk();
        $this->assertContains($pinjaman->id, collect($res->json('data'))->pluck('id')->all());
        $res->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total'], 'rekap']);
        $statusRekap = collect($res->json('rekap'))->pluck('status')->all();
        $this->assertContains('aktif', $statusRekap);

        $resCari = $this->headerToken($token)->getJson('/api/v1/pengurus/list-pinjaman?cari='.urlencode(substr($anggota->nama, 0, 8)))->assertOk();
        $this->assertContains($pinjaman->id, collect($resCari->json('data'))->pluck('id')->all());

        $resAktif = $this->headerToken($token)->getJson('/api/v1/pengurus/list-pinjaman?status=aktif')->assertOk();
        $this->assertTrue(collect($resAktif->json('data'))->every(fn ($row) => $row['status'] === 'aktif'));
        $this->assertContains($pinjaman->id, collect($resAktif->json('data'))->pluck('id')->all());

        $this->headerToken($token)->getJson('/api/v1/pengurus/list-pinjaman?status=salah')->assertUnprocessable();
    }

    public function test_list_simpanan_dan_detail(): void
    {
        $anggota = $this->buatAnggota('TOP-981005');
        $anggota->simpanan()->create([
            'jenis' => 'pokok', 'jumlah' => 50_000,
            'bulan_periode' => now()->format('Y-m'), 'tanggal_input' => now(),
            'input_by' => $anggota->user_id,
        ]);
        $token = $this->tokenIzin('ADM-981005', ['simpanan.lihat']);

        $res = $this->headerToken($token)->getJson('/api/v1/pengurus/list-simpanan?cari='.urlencode(substr($anggota->nama, 0, 8)))->assertOk();
        $baris = collect($res->json('data'))->firstWhere('anggota_id', $anggota->id);
        $this->assertNotNull($baris);
        $this->assertSame(50_000.0, (float) $baris['simpanan_pokok']);
        $this->assertSame(50_000.0, (float) $baris['total_simpanan']);
        $res->assertJsonStructure(['data' => [['anggota_id', 'anggota', 'simpanan_pokok', 'simpanan_wajib', 'dana_sosial', 'total_simpanan']], 'meta']);

        $resDetail = $this->headerToken($token)->getJson("/api/v1/pengurus/list-simpanan/{$anggota->id}")->assertOk();
        $resDetail->assertJsonStructure(['anggota', 'pokok', 'wajib', 'dana_sosial', 'total', 'data']);
        $this->assertEquals(50_000.0, (float) $resDetail->json('total'));

        $this->headerToken($token)->getJson('/api/v1/pengurus/list-simpanan/999999')->assertNotFound();
    }

    public function test_laporan_daftar_data_dan_pdf(): void
    {
        $anggota = $this->buatAnggota('TOP-981006');
        $this->buatPinjamanAktif($anggota);
        $token = $this->tokenIzin('ADM-981006', ['laporan.lihat']);

        $res = $this->headerToken($token)->getJson('/api/v1/laporan')->assertOk();
        $slugs = collect($res->json('data'))->pluck('slug')->all();
        $this->assertContains('pinjaman-per-status', $slugs);
        $this->assertContains('setoran-bulanan', $slugs);
        $res->assertJsonStructure(['data' => [['slug', 'judul', 'deskripsi', 'kategori', 'ikon', 'filter']]]);

        $bulan = now()->format('Y-m');
        $resData = $this->headerToken($token)->getJson("/api/v1/laporan/pinjaman-per-status?dari={$bulan}&sampai={$bulan}")->assertOk();
        $resData->assertJsonStructure(['judul', 'periode', 'kolom', 'angka', 'baris', 'total']);
        $this->assertSame('Rekap Pinjaman per Status', $resData->json('judul'));

        $resSetoran = $this->headerToken($token)->getJson('/api/v1/laporan/setoran-bulanan?tahun='.now()->format('Y'))->assertOk();
        $this->assertSame('Rekap Setoran Bulanan', $resSetoran->json('judul'));

        $this->headerToken($token)->getJson('/api/v1/laporan/tidak-ada')->assertNotFound();

        $resPdf = $this->headerToken($token)->getJson("/api/v1/laporan/pinjaman-per-status/pdf?dari={$bulan}&sampai={$bulan}")->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $resPdf->headers->get('content-type'));
    }

    public function test_notifikasi_daftar_baca_dan_baca_semua(): void
    {
        $anggota = $this->buatAnggota('TOP-981007');
        $this->buatPinjamanAktif($anggota, ['status' => 'diajukan']);
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $res = $this->headerToken($token)->getJson('/api/v1/notifications')->assertOk();
        $data = collect($res->json('data'));
        $this->assertGreaterThanOrEqual(1, $data->count());
        $res->assertJsonStructure(['data' => [['id', 'tipe', 'judul', 'pesan', 'dibaca', 'tanggal']], 'belum_dibaca']);
        $belum = (int) $res->json('belum_dibaca');
        $this->assertGreaterThanOrEqual(1, $belum);
        $idPertama = (string) $data->first()['id'];
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $idPertama);

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/notifications/{$idPertama}/read")
            ->assertOk()
            ->assertJsonPath('message', 'Ditandai sudah dibaca.');

        // Id stabil walau daftar berubah: baca ulang, id pertama (sudah dibaca) tetap ada.
        $resSetelah = $this->headerToken($token)->getJson('/api/v1/notifications')->assertOk();
        $this->assertSame($belum - 1, (int) $resSetelah->json('belum_dibaca'));
        $this->assertContains($idPertama, collect($resSetelah->json('data'))->pluck('id')->all());

        // Id tak dikenal tetap sukses kosong dan tidak mengubah hitungan.
        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/notifications/'.md5('tak-dikenal').'/read')
            ->assertOk();
        $this->assertSame($belum - 1, (int) $this->headerToken($token)->getJson('/api/v1/notifications')->json('belum_dibaca'));

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('message', 'Semua notifikasi ditandai sudah dibaca.');

        $this->headerToken($token)->getJson('/api/v1/notifications')->assertOk()
            ->assertJsonPath('belum_dibaca', 0);
    }

    public function test_pengurus_melihat_notifikasi_antrean(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-981008');
        $this->buatPinjamanAktif($anggota, ['status' => 'diajukan']);
        $token = $this->tokenIzin('ADM-981008', ['pinjaman.tinjau-admin']);

        $res = $this->headerToken($token)->getJson('/api/v1/notifications')->assertOk();
        $judul = collect($res->json('data'))->pluck('judul')->all();
        $this->assertTrue(collect($judul)->some(fn ($j) => str_contains((string) $j, 'verifikasi Admin')));
    }
}

<?php

namespace Tests\Feature;

use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PinjamanMandiriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    private function login(string $noKaryawan): User
    {
        $user = User::where('no_karyawan', $noKaryawan)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    private function ajukan(array $override = []): TestResponse
    {
        return $this->post(route('portal.pinjaman.store'), array_merge([
            'nominal' => 500_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Keperluan pribadi koperasi',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => 'Pengurus Koperasi',
            'persetujuan' => true,
        ], $override));
    }

    public function test_bendahara_mengajukan_lewat_admin_lalu_otomatis_ke_ketua(): void
    {
        $bendahara = $this->login('BEN-000001');

        $this->ajukan()->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('pinjaman', [
            'pengaju_user_id' => $bendahara->id,
            'status' => 'diajukan',
            'cair_oleh_bendahara' => false,
        ]);

        $pinjaman = Pinjaman::where('pengaju_user_id', $bendahara->id)->first();
        $this->assertStringContainsString(
            'Diajukan mandiri oleh Bendahara',
            $pinjaman->catatan_bendahara
        );

        // Admin meneruskan → tahap bendahara dilewati otomatis (tak self-approve)
        $this->login('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Ketua.'])
            ->assertRedirect();

        $pinjaman->refresh();
        $this->assertSame('approved_bendahara', $pinjaman->status);
        $this->assertNotNull($pinjaman->catatan_admin);
    }

    public function test_bendahara_mengajukan_muncul_di_antrean_ketua(): void
    {
        $bendahara = $this->login('BEN-000001');
        $this->ajukan();
        $pinjaman = Pinjaman::where('pengaju_user_id', $bendahara->id)->first();

        $this->login('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Ketua.'])
            ->assertRedirect();

        $this->login('KET-000001');
        $response = $this->get(route('ketua.pinjaman.index'));
        $response->assertOk();

        $masukAntreanKetua = Pinjaman::where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', false)
            ->where('id', $pinjaman->id)
            ->exists();
        $this->assertTrue($masukAntreanKetua);
    }

    public function test_ketua_mengajukan_menunggu_bendahara_lalu_cair_oleh_bendahara(): void
    {
        $ketua = $this->login('KET-000001');

        $this->ajukan()->assertRedirect(route('portal.dashboard'));
        $pinjaman = Pinjaman::where('pengaju_user_id', $ketua->id)->first();

        $this->assertDatabaseHas('pinjaman', [
            'id' => $pinjaman->id,
            'status' => 'diajukan',
            'cair_oleh_bendahara' => true,
        ]);

        // Admin meneruskan lebih dulu
        $this->login('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Bendahara.'])
            ->assertRedirect();

        // Bendahara menyetujui -> masuk status approved_ketua (siap dicairkan di menu Pencairan Pinjaman)
        $this->login('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Data lengkap.', 'nominal' => 500_000])
            ->assertRedirect();

        $pinjaman->refresh();
        $this->assertEquals('approved_ketua', $pinjaman->status);
        $this->assertTrue($pinjaman->cair_oleh_bendahara);

        // Bendahara mencairkan lewat menu Pencairan Pinjaman (+ bukti transfer)
        $this->post(route('bendahara.pencairan-pinjaman.cair', $pinjaman), [
            'tanggal_pencairan' => now()->toDateString(),
            'bukti_pencairan' => UploadedFile::fake()->image('bukti-mandiri.jpg'),
            'catatan' => 'Dana dicairkan.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $pinjaman->refresh();
        $this->assertEquals('aktif', $pinjaman->status);
        $this->assertNotNull($pinjaman->tanggal_pencairan);
        $this->assertNotNull($pinjaman->bukti_pencairan_path);
        $this->assertCount($pinjaman->tenor_bulan, $pinjaman->angsuran()->get());
    }

    public function test_ketua_tidak_melihat_pinjamannya_di_antrean_approval_sendiri(): void
    {
        $ketua = $this->login('KET-000001');
        $this->ajukan();
        $pinjaman = Pinjaman::where('pengaju_user_id', $ketua->id)->first();

        $this->login('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Bendahara.']);

        $this->login('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Data lengkap.', 'nominal' => 500_000]);

        // Pinjaman milik Ketua (cair_oleh_bendahara=true) tidak boleh muncul di antrean
        // persetujuan Ketua, supaya Ketua tidak self-approve/cair.
        $masukAntreanKetua = Pinjaman::where('status', 'approved_bendahara')
            ->where('cair_oleh_bendahara', false)
            ->where('id', $pinjaman->id)
            ->exists();
        $this->assertFalse($masukAntreanKetua);

        // Sebaliknya masuk status approved_ketua & muncul di menu Pencairan Pinjaman Bendahara
        $masukAntreanCairBendahara = Pinjaman::where('status', 'approved_ketua')
            ->where('cair_oleh_bendahara', true)
            ->where('id', $pinjaman->id)
            ->exists();
        $this->assertTrue($masukAntreanCairBendahara);
    }

    public function test_pengawas_bisa_mengajukan_pinjaman_mandiri_hingga_aktif(): void
    {
        $pengawas = $this->login('WAS-000001');
        $this->get(route('portal.dashboard'))->assertOk();

        $this->ajukan(['atas_nama' => 'Pengawas 1 Koperasi'])->assertRedirect(route('portal.dashboard'));
        $pinjaman = Pinjaman::where('pengaju_user_id', $pengawas->id)->sole();
        $this->assertSame('diajukan', $pinjaman->status);

        $this->login('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Teruskan pengawas.'])->assertRedirect();
        $this->assertSame('approved_admin', $pinjaman->fresh()->status);

        $this->login('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Sesuai limit.', 'nominal' => 500_000])->assertRedirect();
        $this->assertSame('approved_bendahara', $pinjaman->fresh()->status);

        $this->login('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Disetujui Ketua.', 'nominal' => 500_000])->assertRedirect();
        $this->assertSame('approved_ketua', $pinjaman->fresh()->status);

        $this->login('BEN-000001');
        $this->post(route('bendahara.pencairan-pinjaman.cair', $pinjaman), [
            'tanggal_pencairan' => now()->toDateString(),
            'bukti_pencairan' => UploadedFile::fake()->image('bukti-was.jpg'),
            'catatan' => 'Dicairkan untuk Pengawas.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('aktif', $pinjaman->fresh()->status);
    }

    public function test_sekretaris_mengajukan_pinjaman_mandiri_langsung_ke_bendahara_lalu_aktif(): void
    {
        $sekretaris = $this->login('ADM-000001');

        $this->ajukan(['atas_nama' => 'Sekretaris Koperasi', 'pin' => '123456'])->assertRedirect(route('portal.dashboard'));
        $pinjaman = Pinjaman::where('pengaju_user_id', $sekretaris->id)->sole();
        $this->assertSame('approved_admin', $pinjaman->status);

        // Tidak muncul di antrean approval Sekretaris sendiri
        $this->get(route('persetujuan.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'inbox.antrean',
                fn ($antrean) => ! collect($antrean)->contains(fn ($i) => ($i['detail']['id'] ?? null) === $pinjaman->id)
            ));

        $this->login('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'OK bendahara.', 'nominal' => 500_000])->assertRedirect();
        $this->assertSame('approved_bendahara', $pinjaman->fresh()->status);

        $this->login('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'OK ketua.', 'nominal' => 500_000])->assertRedirect();

        $this->login('BEN-000001');
        $this->post(route('bendahara.pencairan-pinjaman.cair', $pinjaman), [
            'tanggal_pencairan' => now()->toDateString(),
            'bukti_pencairan' => UploadedFile::fake()->image('bukti-adm.jpg'),
            'catatan' => 'Cair.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('aktif', $pinjaman->fresh()->status);
    }

    public function test_bendahara_mencairkan_pinjaman_mandiri_setelah_ketua_setuju_wajib_bukti(): void
    {
        $bendahara = $this->login('BEN-000001');
        $this->ajukan()->assertRedirect(route('portal.dashboard'));
        $pinjaman = Pinjaman::where('pengaju_user_id', $bendahara->id)->sole();

        $this->login('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Ke Ketua.'])->assertRedirect();

        $this->login('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Ketua setujui.', 'nominal' => 500_000])->assertRedirect();
        $this->assertSame('approved_ketua', $pinjaman->fresh()->status);

        $this->login('BEN-000001');
        // Tanpa bukti_pencairan wajib ditolak
        $this->post(route('bendahara.pencairan-pinjaman.cair', $pinjaman), [
            'tanggal_pencairan' => now()->toDateString(),
        ])->assertSessionHasErrors();
        $this->assertSame('approved_ketua', $pinjaman->fresh()->status);

        // Dengan bukti_pencairan berhasil menjadi aktif
        $this->post(route('bendahara.pencairan-pinjaman.cair', $pinjaman), [
            'tanggal_pencairan' => now()->toDateString(),
            'bukti_pencairan' => UploadedFile::fake()->image('bukti-ben.jpg'),
            'catatan' => 'Transfer mandiri tersimpan.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('aktif', $pinjaman->fresh()->status);
    }

    public function test_limit_dan_percepatan_mandiri_bendahara_serta_ketua_tanpa_deadlock(): void
    {
        // Bendahara mengajukan tambah limit → langsung approved_bendahara → disetujui Ketua
        $bendahara = $this->login('BEN-000001');
        $bendahara->anggota->update(['limit_custom' => 5_000_000]);
        $this->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 8_000_000,
            'keterangan' => 'Naik limit kebutuhan bendahara',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $pengLimitBen = PengajuanLimit::where('anggota_id', $bendahara->anggota->id)->sole();
        $this->assertSame('approved_bendahara', $pengLimitBen->status);

        $this->login('KET-000001');
        $this->post(route('ketua.pengajuan-limit.approve', $pengLimitBen), [
            'limit_disetujui' => 8_000_000,
            'catatan' => 'Disetujui Ketua.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('disetujui', $pengLimitBen->fresh()->status);

        // Ketua mengajukan tambah limit → langsung finalisasi di tahap Bendahara
        $ketua = $this->login('KET-000001');
        $ketua->anggota->update(['limit_custom' => 5_000_000]);
        $this->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 9_000_000,
            'keterangan' => 'Naik limit kebutuhan ketua',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $pengLimitKet = PengajuanLimit::where('anggota_id', $ketua->anggota->id)->sole();
        $this->assertSame('diajukan', $pengLimitKet->status);

        $this->login('BEN-000001');
        $this->post(route('bendahara.pengajuan-limit.approve', $pengLimitKet), [
            'limit_disetujui' => 9_000_000,
            'catatan' => 'Disetujui Bendahara tanpa self-approval Ketua.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('disetujui', $pengLimitKet->fresh()->status);

        // Ketua memiliki pinjaman aktif → mengajukan percepatan → langsung aktif saat Bendahara approve
        $pinjamanKetua = Pinjaman::create([
            'anggota_id' => $ketua->anggota->id,
            'pengaju_user_id' => $ketua->id,
            'nominal' => 1_200_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Tes percepatan ketua',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonth(),
            'tanggal_pencairan' => now()->subMonth(),
        ]);
        for ($i = 1; $i <= 4; $i++) {
            $pinjamanKetua->angsuran()->create([
                'cicilan_ke' => $i,
                'nominal_pokok' => 300_000,
                'nominal_bunga' => 12_000,
                'total_bayar' => 312_000,
                'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->addMonths($i - 1)->endOfMonth(),
            ]);
        }

        $this->login('KET-000001');
        $this->post(route('portal.percepatan.store'), [
            'pinjaman_id' => $pinjamanKetua->id,
            'tipe' => 'percepat',
            'tenor_baru' => 2,
            'keterangan' => 'Ingin lebih cepat lunas',
        ])->assertRedirect();

        $percKet = PengajuanPercepatan::where('pinjaman_id', $pinjamanKetua->id)->sole();
        $this->assertSame('diajukan', $percKet->status);

        $this->login('BEN-000001');
        $this->post(route('bendahara.percepatan.approve', $percKet), [
            'catatan' => 'Disetujui Bendahara.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('aktif', $percKet->fresh()->status);
    }
}

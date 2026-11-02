<?php

namespace Tests\Feature;

use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class NotifikasiSidebarTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_badge_antrean_sidebar_langsung_berubah_setelah_approve(): void
    {
        Queue::fake();

        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);
        $this->post(route('portal.pinjaman.store'), [
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Modal usaha sampingan',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
        ])->assertStatus(302);

        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->sole();
        $sebelum = Pinjaman::where('status', 'diajukan')->count();

        $this->masuk('ADM-000001');
        $this->get(route('admin.pinjaman.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.menunggu_tinjauan_admin', $sebelum));

        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap, diteruskan.'])
            ->assertRedirect(route('admin.pinjaman.index'));

        $this->get(route('admin.pinjaman.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.menunggu_tinjauan_admin', $sebelum - 1));
    }
}

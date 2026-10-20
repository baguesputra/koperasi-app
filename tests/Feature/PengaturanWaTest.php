<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PengaturanWaTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->masuk('ADM-000001');
        $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    private function gunakanWaha(): void
    {
        config([
            'services.wa.gateway' => 'waha',
            'services.waha.url' => 'https://waha-test.test',
            'services.waha.api_key' => 'kunci-waha',
            'services.waha.session' => 'waha',
            'services.waha.timeout' => 5,
            'services.waha.verify_tls' => true,
        ]);
    }

    public function test_status_waha_working_read_only(): void
    {
        $this->gunakanWaha();
        Http::fake([
            'https://waha-test.test/api/sessions/waha' => Http::response(['name' => 'waha', 'status' => 'WORKING']),
        ]);

        $this->getJson(route('pengaturan.wa.data'))
            ->assertOk()
            ->assertJson([
                'terhubung' => true,
                'layanan' => true,
                'bisa_kelola' => false,
                'gateway' => 'waha',
                'qr' => null,
            ]);
    }

    public function test_status_waha_session_tidak_ditemukan(): void
    {
        $this->gunakanWaha();
        Http::fake([
            'https://waha-test.test/api/sessions/waha' => Http::response(['message' => 'Not found'], 404),
        ]);

        $this->getJson(route('pengaturan.wa.data'))
            ->assertOk()
            ->assertJson([
                'terhubung' => false,
                'layanan' => true,
                'bisa_kelola' => false,
                'gateway' => 'waha',
                'qr' => null,
            ]);
    }

    public function test_logout_waha_ditolak(): void
    {
        $this->gunakanWaha();
        Http::fake();

        $this->postJson(route('pengaturan.wa.logout'))
            ->assertForbidden()
            ->assertJson(['message' => 'Sesi WhatsApp dikelola oleh penyedia layanan.']);

        Http::assertNothingSent();
    }

    public function test_status_baileys_menampilkan_qr(): void
    {
        config([
            'services.wa.gateway' => 'baileys',
            'services.wa.url' => 'http://baileys-test:3000',
            'services.wa.token' => 'token-rahasia',
            'services.wa.timeout' => 5,
        ]);
        Http::fake([
            'http://baileys-test:3000/status' => Http::response(['connected' => false, 'hasQR' => true]),
            'http://baileys-test:3000/qr' => Http::response(['qr' => 'data:image/png;base64,abc']),
        ]);

        $this->getJson(route('pengaturan.wa.data'))
            ->assertOk()
            ->assertJson([
                'terhubung' => false,
                'layanan' => true,
                'bisa_kelola' => true,
                'gateway' => 'baileys',
                'qr' => 'data:image/png;base64,abc',
            ]);
    }
}

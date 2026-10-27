<?php

namespace Tests\Feature\Auth;

use App\Models\MobileSsoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSsoExchangeTest extends TestCase
{
    use RefreshDatabase;

    private function verifier(): string
    {
        return str_repeat('v', 64);
    }

    private function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function buatKode(?string $state = null, ?string $verifier = null): array
    {
        $state ??= str_repeat('s', 64);
        $verifier ??= $this->verifier();
        $code = bin2hex(random_bytes(32));
        $user = User::factory()->create(['status' => 'aktif']);
        MobileSsoCode::create([
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $code),
            'code_challenge' => $this->challenge($verifier),
            'state' => $state,
            'expires_at' => now()->addMinute(),
        ]);

        return [$code, $state, $verifier, $user];
    }

    public function test_exchange_menerbitkan_token_dan_kode_hanya_bisa_dipakai_sekali(): void
    {
        [$code, $state, $verifier] = $this->buatKode();
        $payload = ['code' => $code, 'state' => $state, 'code_verifier' => $verifier];

        $this->postJson('/api/v1/sso/mobile/exchange', $payload)
            ->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'permissions']]);
        $this->postJson('/api/v1/sso/mobile/exchange', $payload)->assertUnprocessable();
    }

    public function test_exchange_menolak_state_verifier_dan_kode_kedaluwarsa(): void
    {
        [$code, $state, $verifier] = $this->buatKode();
        $this->postJson('/api/v1/sso/mobile/exchange', [
            'code' => $code, 'state' => str_repeat('x', 64), 'code_verifier' => $verifier,
        ])->assertUnprocessable();

        $this->postJson('/api/v1/sso/mobile/exchange', [
            'code' => $code, 'state' => $state, 'code_verifier' => str_repeat('x', 64),
        ])->assertUnprocessable();

        $expired = $this->buatKode(str_repeat('e', 64));
        MobileSsoCode::where('code_hash', hash('sha256', $expired[0]))->update(['expires_at' => now()->subSecond()]);
        $this->postJson('/api/v1/sso/mobile/exchange', [
            'code' => $expired[0], 'state' => $expired[1], 'code_verifier' => $expired[2],
        ])->assertUnprocessable();
    }
}

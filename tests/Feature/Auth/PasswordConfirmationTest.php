<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'pin' => Hash::make('123456'),
        ]);

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertStatus(200);
    }

    public function test_pin_can_be_confirmed(): void
    {
        $user = User::factory()->create([
            'pin' => Hash::make('123456'),
        ]);

        $response = $this->actingAs($user)->post('/confirm-password', [
            'pin' => '123456',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_pin_is_not_confirmed_with_invalid_pin(): void
    {
        $user = User::factory()->create([
            'pin' => Hash::make('123456'),
        ]);

        $response = $this->actingAs($user)->post('/confirm-password', [
            'pin' => '999999',
        ]);

        $response->assertSessionHasErrors('pin');
    }
}

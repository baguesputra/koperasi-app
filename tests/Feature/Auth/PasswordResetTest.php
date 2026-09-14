<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_jalur_reset_password_dinonaktifkan_akses_gate(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'x@t.id'])->assertNotFound();
        $this->get('/reset-password/token-uji')->assertNotFound();
        $this->post('/reset-password', [])->assertNotFound();
    }
}

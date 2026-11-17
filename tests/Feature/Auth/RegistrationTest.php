<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_redirects_to_login(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('login'));
    }

    public function test_public_registration_cannot_create_an_unassigned_operational_account(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Pengguna',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertForbidden();
        $this->assertDatabaseCount('pengguna', 0);
    }
}

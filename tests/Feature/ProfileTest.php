<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = Pengguna::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = Pengguna::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test Pengguna',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test Pengguna', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = Pengguna::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test Pengguna',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $user = Pengguna::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertForbidden();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh());
    }

    public function test_branch_user_cannot_update_their_own_profile(): void
    {
        $user = $this->createBranchUser();
        $originalName = $user->name;
        $originalEmail = $user->email;

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
            ]);

        $response->assertForbidden();

        $user->refresh();
        $this->assertSame($originalName, $user->name);
        $this->assertSame($originalEmail, $user->email);
    }

    public function test_branch_user_cannot_delete_their_own_account(): void
    {
        $user = $this->createBranchUser();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', ['password' => 'password']);

        $response->assertForbidden();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh());
    }

    public function test_branch_user_profile_page_is_read_only(): void
    {
        $user = $this->createBranchUser();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Profil dan password pengguna cabang hanya dapat diubah oleh admin.')
            ->assertDontSee('action="'.route('profile.update').'"', false)
            ->assertDontSee('action="'.route('password.update').'"', false)
            ->assertDontSee('action="'.route('profile.destroy').'"', false);
    }

    private function createBranchUser(): Pengguna
    {
        $branch = Cabang::create([
            'kode_cabang' => fake()->unique()->bothify('CB-####'),
            'nama_cabang' => 'Cabang Uji',
        ]);

        return Pengguna::factory()->create([
            'role' => 'toko_cabang',
            'branch_id' => $branch->id,
        ]);
    }
}

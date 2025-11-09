<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Auth;

use App\Domain\Admin\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Admin\AdminApiTestCase;

class AuthEndpointsTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_login_with_valid_credentials(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson(route('admin.api.login'), [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.admin.email', $admin->email)
            ->assertJsonPath('data.token_type', 'Bearer');
    }

    /** @test */
    public function login_fails_with_invalid_credentials(): void
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson(route('admin.api.login'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJsonPath('code', 'AUTH_ERROR');
    }

    /** @test */
    public function login_fails_for_inactive_admin(): void
    {
        Admin::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson(route('admin.api.login'), [
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ])->assertStatus(401)
            ->assertJsonPath('errors.error_code', 'AUTH_ACCOUNT_INACTIVE');
    }

    /** @test */
    public function authenticated_admin_can_view_profile(): void
    {
        $admin = $this->signInAdmin();

        $this->getJson(route('admin.api.me'))
            ->assertOk()
            ->assertJsonPath('data.email', $admin->email);
    }

    /** @test */
    public function viewing_profile_requires_authentication(): void
    {
        $this->getJson(route('admin.api.me'))
            ->assertUnauthorized();
    }

    /** @test */
    public function authenticated_admin_can_logout(): void
    {
        $this->signInAdmin();

        $this->postJson(route('admin.api.logout'))
            ->assertOk()
            ->assertJsonPath('message', 'Logout successful');
    }

    /** @test */
    public function logout_requires_authentication(): void
    {
        $this->postJson(route('admin.api.logout'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_request_password_reset_link(): void
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $this->postJson(route('admin.api.forgot-password'), [
            'email' => 'admin@example.com',
        ])->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com');

        $this->assertDatabaseHas('password_resets', [
            'email' => 'admin@example.com',
        ]);
    }

    /** @test */
    public function password_reset_request_for_unknown_admin_returns_error(): void
    {
        $this->postJson(route('admin.api.forgot-password'), [
            'email' => 'unknown@example.com',
        ])->assertStatus(401)
            ->assertJsonPath('code', 'AUTH_ERROR');
    }

    /** @test */
    public function admin_can_reset_password_with_valid_token(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $plainToken = 'reset-token';
        DB::table('password_resets')->insert([
            'email' => 'admin@example.com',
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $response = $this->postJson(route('admin.api.reset-password'), [
            'email' => 'admin@example.com',
            'token' => $plainToken,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password reset successful. Please login with your new password.');

        $this->assertTrue(Hash::check('new-password', $admin->fresh()->password));
        $this->assertDatabaseMissing('password_resets', ['email' => 'admin@example.com']);
    }

    /** @test */
    public function reset_password_fails_with_invalid_token(): void
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
        ]);

        DB::table('password_resets')->insert([
            'email' => 'admin@example.com',
            'token' => Hash::make('actual-token'),
            'created_at' => now(),
        ]);

        $this->postJson(route('admin.api.reset-password'), [
            'email' => 'admin@example.com',
            'token' => 'wrong-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(401)
            ->assertJsonPath('code', 'AUTH_ERROR');
    }
}


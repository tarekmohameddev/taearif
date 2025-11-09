<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Models\User as TenantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateUserPasswordTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_a_user_password(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'password' => Hash::make('OldPassword1!'),
        ]);

        $response = $this->putJson(
            route('admin.api.users.password', $tenant->uuid),
            [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->uuid)
            ->assertJsonPath('data.status.status_code', $tenant->status);

        $this->assertTrue(
            Hash::check('NewPassword123!', $tenant->fresh()->password),
            'User password was not updated.'
        );
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_password_payload(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
        ]);

        $response = $this->putJson(
            route('admin.api.users.password', $tenant->uuid),
            [
                'password' => 'short',
                'password_confirmation' => 'short',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $tenantUuid = (string) Str::uuid();

        $this->putJson(
            route('admin.api.users.password', $tenantUuid),
            [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_user_does_not_exist(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.users.password', (string) Str::uuid()),
            [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]
        );

        $response->assertNotFound();
    }
}


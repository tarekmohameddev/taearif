<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Models\User as TenantUser;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateUserTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_a_tenant_user(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'email' => 'old@example.com',
            'first_name' => 'Old',
        ]);

        $payload = [
            'first_name' => 'Updated',
            'email' => 'new@example.com',
            'status' => 0,
        ];

        $response = $this->putJson(
            route('admin.api.users.update', $tenant->uuid),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.first_name', 'Updated')
            ->assertJsonPath('data.status.status_code', 0);

        $this->assertDatabaseHas('users', [
            'id' => $tenant->id,
            'first_name' => 'Updated',
            'email' => 'new@example.com',
            'status' => 0,
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_payload(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        $response = $this->putJson(
            route('admin.api.users.update', $tenant->uuid),
            ['email' => 'not-an-email']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $tenantUuid = (string) Str::uuid();

        $response = $this->putJson(
            route('admin.api.users.update', $tenantUuid),
            ['first_name' => 'Updated']
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_uuid_does_not_exist(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.users.update', (string) Str::uuid()),
            ['first_name' => 'Updated']
        );

        $response->assertNotFound();
    }
}


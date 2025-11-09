<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Models\User as TenantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageUsersTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_users(): void
    {
        $this->signInAdmin();

        $tenants = TenantUser::factory()->count(2)->create([
            'account_type' => 'tenant',
        ]);

        $response = $this->getJson(route('admin.api.users.index'));

        $response->assertOk()
            ->assertJsonPath('data.data.0.id', $tenants->first()->uuid)
            ->assertJsonPath('data.meta.total', $response->json('data.meta.total'));
    }

    /** @test */
    public function listing_users_requires_authentication(): void
    {
        $this->getJson(route('admin.api.users.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_create_a_user(): void
    {
        $this->signInAdmin();

        $payload = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'username' => 'janedoe',
            'password' => 'Secret123!',
            'phone' => '1234567890',
            'company_name' => 'Acme Inc.',
        ];

        $response = $this->postJson(
            route('admin.api.users.store'),
            $payload
        );

        $response->assertCreated()
            ->assertJsonPath('data.email', 'jane.doe@example.com')
            ->assertJsonPath('data.first_name', 'Jane');

        $user = TenantUser::where('email', 'jane.doe@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Secret123!', $user->password));
        $this->assertSame('tenant', $user->account_type);
    }

    /** @test */
    public function validation_errors_are_returned_when_creating_a_user_with_invalid_payload(): void
    {
        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.users.store'),
            [
                'first_name' => '',
                'email' => 'invalid-email',
                'password' => 'short',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'email', 'password']);
    }

    /** @test */
    public function creating_a_user_requires_authentication(): void
    {
        $this->postJson(
            route('admin.api.users.store'),
            [
                'first_name' => 'John',
                'email' => 'john@example.com',
                'password' => 'Password123!',
            ]
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_a_user(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        $response = $this->getJson(
            route('admin.api.users.show', $tenant->uuid)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->uuid)
            ->assertJsonPath('data.email', $tenant->email);
    }

    /** @test */
    public function viewing_a_user_requires_authentication(): void
    {
        $tenantUuid = (string) Str::uuid();

        $this->getJson(
            route('admin.api.users.show', $tenantUuid)
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_viewing_a_missing_user(): void
    {
        $this->signInAdmin();

        $this->getJson(
            route('admin.api.users.show', (string) Str::uuid())
        )->assertNotFound();
    }

    /** @test */
    public function admin_can_delete_a_user(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        $response = $this->deleteJson(
            route('admin.api.users.destroy', $tenant->uuid)
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $tenant->id,
        ]);
    }

    /** @test */
    public function deleting_a_user_requires_authentication(): void
    {
        $tenant = TenantUser::factory()->create();

        $this->deleteJson(
            route('admin.api.users.destroy', $tenant->uuid)
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_deleting_a_missing_user(): void
    {
        $this->signInAdmin();

        $this->deleteJson(
            route('admin.api.users.destroy', (string) Str::uuid())
        )->assertNotFound();
    }
}


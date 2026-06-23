<?php

namespace Tests\Feature;

use App\Models\ApiCustomer;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardContactMessagesTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'contact_messages', 'api_customers', 'api_permissions', 'api_model_has_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    private function grantPermissions(User $tenant, array $permissions): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $tenant->id);
        $registrar->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            try {
                $permission = Permission::findByName($permissionName, 'sanctum');
            } catch (\Throwable $e) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'sanctum',
                    'team_id' => $tenant->id,
                ]);
            }

            $tenant->givePermissionTo($permission);
        }

        $registrar->forgetCachedPermissions();
    }

    private function seedMessage(User $tenant, array $overrides = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '+966500000001',
            'message' => 'Test message body',
            'source' => 'contact_form_section',
            'is_read' => false,
            'status' => 'active',
            'metadata' => [],
        ], $overrides));
    }

    public function test_list_messages_with_filters(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, ['contact_messages.view']);
        Sanctum::actingAs($tenant);

        $this->seedMessage($tenant);
        $this->seedMessage($tenant, [
            'customer_name' => 'Archived User',
            'status' => 'archived',
            'is_read' => true,
        ]);

        $res = $this->getJson('/api/v1/contact-messages?status=active');
        $res->assertOk()->assertJson(['success' => true]);
        $this->assertCount(1, $res->json('data'));

        $search = $this->getJson('/api/v1/contact-messages?search=Archived');
        $search->assertOk();
        $this->assertCount(0, $search->json('data'));
    }

    public function test_tenant_isolation(): void
    {
        $this->skipIfMissingSchema();
        $tenantA = User::factory()->create();
        $tenantB = User::factory()->create();
        $this->grantPermissions($tenantA, ['contact_messages.view']);
        Sanctum::actingAs($tenantA);

        $message = $this->seedMessage($tenantB);

        $this->getJson('/api/v1/contact-messages/' . $message->id)->assertStatus(404);
    }

    public function test_unread_count_only_active(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, ['contact_messages.view']);
        Sanctum::actingAs($tenant);

        $this->seedMessage($tenant);
        $this->seedMessage($tenant, ['status' => 'archived']);

        $res = $this->getJson('/api/v1/contact-messages/unread-count');
        $res->assertOk()->assertJson([
            'success' => true,
            'data' => ['unread_count' => 1],
        ]);
    }

    public function test_stats_totals(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, ['contact_messages.view']);
        Sanctum::actingAs($tenant);

        $this->seedMessage($tenant);
        $this->seedMessage($tenant, ['status' => 'archived', 'is_read' => true]);

        $res = $this->getJson('/api/v1/contact-messages/stats');
        $res->assertOk()->assertJson([
            'success' => true,
            'data' => [
                'total' => 2,
                'unread' => 1,
                'archived' => 1,
            ],
        ]);
    }

    public function test_forbidden_without_permission(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        Sanctum::actingAs($tenant);

        $this->getJson('/api/v1/contact-messages')->assertStatus(403);
    }

    public function test_mark_read_and_unread(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, ['contact_messages.view', 'contact_messages.update']);
        Sanctum::actingAs($tenant);

        $message = $this->seedMessage($tenant);

        $read = $this->patchJson('/api/v1/contact-messages/' . $message->id . '/read');
        $read->assertOk()->assertJsonPath('data.is_read', true);
        $this->assertNotNull($read->json('data.read_at'));

        $this->patchJson('/api/v1/contact-messages/' . $message->id . '/read')->assertOk();

        $unread = $this->patchJson('/api/v1/contact-messages/' . $message->id . '/unread');
        $unread->assertOk()->assertJsonPath('data.is_read', false);
        $unread->assertJsonPath('data.read_at', null);
    }

    public function test_read_all(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, ['contact_messages.update']);
        Sanctum::actingAs($tenant);

        $this->seedMessage($tenant);
        $this->seedMessage($tenant, ['customer_email' => 'other@example.com']);
        $this->seedMessage($tenant, ['status' => 'archived']);

        $res = $this->patchJson('/api/v1/contact-messages/read-all');
        $res->assertOk()->assertJsonPath('data.updated_count', 2);
    }

    public function test_bulk_actions(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, [
            'contact_messages.view',
            'contact_messages.update',
            'contact_messages.delete',
        ]);
        Sanctum::actingAs($tenant);

        $m1 = $this->seedMessage($tenant);
        $m2 = $this->seedMessage($tenant, ['customer_email' => 'b@example.com']);

        $archive = $this->postJson('/api/v1/contact-messages/bulk', [
            'action' => 'archive',
            'ids' => [$m1->id],
        ]);
        $archive->assertOk();
        $this->assertEquals('archived', $m1->fresh()->status);
        $this->assertArrayHasKey('archived_at', $m1->fresh()->metadata);

        $this->patchJson('/api/v1/contact-messages/' . $m1->id . '/unarchive')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
        $this->assertArrayHasKey('unarchived_at', $m1->fresh()->metadata);

        $delete = $this->postJson('/api/v1/contact-messages/bulk', [
            'action' => 'delete',
            'ids' => [$m2->id, 99999],
        ]);
        $delete->assertOk()->assertJsonPath('data.updated_count', 1);
        $delete->assertJsonPath('data.failed_ids', [99999]);
        $this->assertSoftDeleted('contact_messages', ['id' => $m2->id]);
    }

    public function test_delete_requires_permission(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, ['contact_messages.update']);
        Sanctum::actingAs($tenant);

        $message = $this->seedMessage($tenant);

        $this->deleteJson('/api/v1/contact-messages/' . $message->id)->assertStatus(403);
    }

    public function test_create_customer_success(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, [
            'contact_messages.view',
            'contact_messages.create_customer',
        ]);
        Sanctum::actingAs($tenant);

        $message = $this->seedMessage($tenant);

        $res = $this->postJson('/api/v1/contact-messages/' . $message->id . '/create-customer');
        $res->assertStatus(201)->assertJsonPath('data.customer.customers_hub_stage_id', 'new_lead');

        $message->refresh();
        $this->assertNotNull($message->customer_id);
        $this->assertDatabaseHas('api_customers', [
            'id' => $message->customer_id,
            'source' => 'website_contact_message',
            'source_id' => $message->id,
        ]);
    }

    public function test_create_customer_returns_409_when_already_linked(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, ['contact_messages.create_customer']);
        Sanctum::actingAs($tenant);

        $customer = ApiCustomer::create([
            'user_id' => $tenant->id,
            'name' => 'Existing',
            'phone_number' => '+966500000099',
        ]);

        $message = $this->seedMessage($tenant, ['customer_id' => $customer->id]);

        $this->postJson('/api/v1/contact-messages/' . $message->id . '/create-customer')
            ->assertStatus(409);
    }

    public function test_create_customer_returns_422_without_name_or_phone(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, ['contact_messages.create_customer']);
        Sanctum::actingAs($tenant);

        $message = $this->seedMessage($tenant, [
            'customer_name' => null,
            'customer_phone' => null,
            'customer_email' => 'only@email.com',
        ]);

        $this->postJson('/api/v1/contact-messages/' . $message->id . '/create-customer')
            ->assertStatus(422);
    }

    public function test_link_customer_idempotent_and_force(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, ['contact_messages.update']);
        Sanctum::actingAs($tenant);

        $customerA = ApiCustomer::create([
            'user_id' => $tenant->id,
            'name' => 'Customer A',
            'phone_number' => '+966500000010',
        ]);
        $customerB = ApiCustomer::create([
            'user_id' => $tenant->id,
            'name' => 'Customer B',
            'phone_number' => '+966500000011',
        ]);

        $message = $this->seedMessage($tenant, ['customer_id' => $customerA->id]);

        $this->postJson('/api/v1/contact-messages/' . $message->id . '/link-customer', [
            'customer_id' => $customerA->id,
        ])->assertOk();

        $this->postJson('/api/v1/contact-messages/' . $message->id . '/link-customer', [
            'customer_id' => $customerB->id,
        ])->assertStatus(409);

        $this->postJson('/api/v1/contact-messages/' . $message->id . '/link-customer', [
            'customer_id' => $customerB->id,
            'force' => true,
        ])->assertOk();

        $this->assertEquals($customerB->id, $message->fresh()->customer_id);
    }

    public function test_customer_message_history(): void
    {
        $this->skipIfMissingSchema();
        $tenant = User::factory()->create();
        $this->grantPermissions($tenant, [
            'contact_messages.view',
            'customers_hub_customers.view',
        ]);
        Sanctum::actingAs($tenant);

        $customer = ApiCustomer::create([
            'user_id' => $tenant->id,
            'name' => 'History Customer',
            'phone_number' => '+966500000020',
        ]);

        $this->seedMessage($tenant, ['customer_id' => $customer->id]);
        $this->seedMessage($tenant, ['customer_id' => null]);

        $res = $this->getJson('/api/v1/customers/' . $customer->id . '/contact-messages');
        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Api\UserApiCustomerType;
use App\Models\ApiCustomer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreCustomerTypeIdTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'api_customers', 'users_api_customers_types'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        $this->ensureNewLeadHubStage();
    }

    private function ensureNewLeadHubStage(): void
    {
        if (! Schema::hasTable('customers_hub_stages')) {
            return;
        }

        if (DB::table('customers_hub_stages')->where('stage_id', 'new_lead')->exists()) {
            return;
        }

        $row = [
            'stage_id' => 'new_lead',
            'stage_name_ar' => 'عميل جديد',
            'stage_name_en' => 'New Lead',
            'color' => '#3b82f6',
            'order' => 1,
            'description' => 'Initial inquiry from any source',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('customers_hub_stages', 'user_id')) {
            $row['user_id'] = null;
        }
        if (Schema::hasColumn('customers_hub_stages', 'is_system')) {
            $row['is_system'] = true;
        }

        DB::table('customers_hub_stages')->insert($row);
    }

    private function createTenant(): User
    {
        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
        ]);

        // Tenants are seeded with the default CRM types on creation; these tests
        // drive the type set explicitly, so start from a clean slate.
        UserApiCustomerType::where('user_id', $tenant->id)->delete();
        Cache::forget("customer_defaults:{$tenant->id}");

        return $tenant;
    }

    private function createType(User $owner, array $overrides = []): UserApiCustomerType
    {
        return UserApiCustomerType::create(array_merge([
            'user_id' => $owner->id,
            'name' => 'Lead',
            'value' => 'lead_' . uniqid(),
            'color' => '#ccc',
            'icon' => '',
            'order' => 1,
            'is_active' => true,
        ], $overrides));
    }

    /** @test */
    public function store_omitting_type_id_uses_first_active_type_by_order(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Cache::forget("customer_defaults:{$tenant->id}");

        $second = $this->createType($tenant, [
            'name' => 'Second',
            'value' => 'second_' . uniqid(),
            'order' => 20,
        ]);
        $first = $this->createType($tenant, [
            'name' => 'First',
            'value' => 'first_' . uniqid(),
            'order' => 1,
        ]);
        $this->createType($tenant, [
            'name' => 'Inactive',
            'value' => 'inactive_' . uniqid(),
            'order' => 0,
            'is_active' => false,
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/customers', [
            'name' => 'Default Type Customer',
            'phone_number' => '+9665' . random_int(10000000, 99999999),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.type_id', $first->id);

        $this->assertNotEquals($second->id, $response->json('data.type_id'));

        $this->assertDatabaseHas('api_customers', [
            'id' => $response->json('data.id'),
            'type_id' => $first->id,
            'user_id' => $tenant->id,
        ]);
    }

    /** @test */
    public function store_falls_back_to_direct_lookup_when_cached_default_is_null(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();

        // Simulate defaults warmed before the tenant's CRM types were seeded.
        Cache::put("customer_defaults:{$tenant->id}", [
            'type_id' => null,
            'priority_id' => null,
            'procedure_id' => null,
        ], now()->addMinutes(5));

        $type = $this->createType($tenant, [
            'name' => 'Seeded Later',
            'value' => 'seeded_later_' . uniqid(),
            'order' => 1,
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/customers', [
            'name' => 'Stale Cache Customer',
            'phone_number' => '+9665' . random_int(10000000, 99999999),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type_id', $type->id);
    }

    /** @test */
    public function store_with_invalid_type_id_returns_422(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $other = $this->createTenant();
        $foreignType = $this->createType($other);

        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/customers', [
            'name' => 'Bad Type Customer',
            'phone_number' => '+9665' . random_int(10000000, 99999999),
            'type_id' => $foreignType->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonValidationErrors(['type_id'], 'errors');

        $this->assertSame(0, ApiCustomer::where('user_id', $tenant->id)->where('name', 'Bad Type Customer')->count());
    }

    /** @test */
    public function store_with_valid_type_id_uses_provided_value(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Cache::forget("customer_defaults:{$tenant->id}");

        $defaultType = $this->createType($tenant, [
            'name' => 'Default',
            'value' => 'default_' . uniqid(),
            'order' => 1,
        ]);
        $chosenType = $this->createType($tenant, [
            'name' => 'Chosen',
            'value' => 'chosen_' . uniqid(),
            'order' => 5,
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/customers', [
            'name' => 'Explicit Type Customer',
            'phone_number' => '+9665' . random_int(10000000, 99999999),
            'type_id' => $chosenType->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.type_id', $chosenType->id);

        $this->assertNotEquals($defaultType->id, $response->json('data.type_id'));
    }
}

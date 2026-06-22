<?php

namespace Tests\Feature\Api;

use App\Models\Api\Crm\CrmRequest;
use App\Models\Api\UserApiCustomerStage;
use App\Models\ApiCustomer;
use App\Models\Logs\PropertyLog;
use App\Models\Property\PropertyCrmRelation;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertySoldCrmCloseTest extends TestCase
{
    use EnsuresPropertyStatusColumns;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'user_properties', 'property_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    public function test_sold_closes_linked_crm_requests(): void
    {
        if (! Schema::hasTable('crm_requests') || ! Schema::hasTable('users_api_customers_stages')) {
            $this->markTestSkipped('CRM tables not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        [$openStage, $closedStage] = $this->createCustomerStages($user->id);

        $request = CrmRequest::create([
            'user_id' => $user->id,
            'stage_id' => $openStage->id,
            'customer_id' => $this->createCustomer($user->id)->id,
            'property_id' => $property->id,
            'position' => 0,
        ]);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Deal closed',
        ])->assertOk()
            ->assertJsonPath('data.crm.success', true)
            ->assertJsonPath('data.crm.closed_requests', 1);

        $this->assertSame($closedStage->id, (int) CrmRequest::find($request->id)->stage_id);
    }

    public function test_sold_closes_users_property_requests(): void
    {
        if (! Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table not available.');
        }

        if (! Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            $this->markTestSkipped('customers_hub_stage_id column required.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        $requestId = $this->createPropertyRequest($user->id, [
            'initial_property_id' => $property->id,
            'customers_hub_stage_id' => 'new_lead',
        ]);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
        ])->assertOk()
            ->assertJsonPath('data.crm.success', true)
            ->assertJsonPath('data.crm.closed_requests', 1);

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertSame('deal_completed', $row->customers_hub_stage_id);
        $this->assertSame(1, (int) $row->is_read);
    }

    public function test_sold_with_assigned_customer_updates_customer_stage(): void
    {
        if (! Schema::hasTable('api_customers') || ! Schema::hasTable('api_customer_assigned_property')) {
            $this->markTestSkipped('Customer assignment tables not available.');
        }

        if (! Schema::hasTable('users_api_customers_stages')) {
            $this->markTestSkipped('users_api_customers_stages table not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        [$openStage, $closedStage] = $this->createCustomerStages($user->id);
        $customer = $this->createCustomer($user->id, ['stage_id' => $openStage->id]);

        DB::table('api_customer_assigned_property')->insert([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
        ])->assertOk()
            ->assertJsonPath('data.crm.closed_customers', 1);

        $this->assertSame($closedStage->id, (int) ApiCustomer::find($customer->id)->stage_id);
    }

    public function test_sold_without_linked_crm_returns_zero_counts(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
        ])->assertOk()
            ->assertJsonPath('data.crm.closed_requests', 0)
            ->assertJsonPath('data.crm.closed_customers', 0)
            ->assertJsonPath('data.crm.success', true);
    }

    public function test_rent_unit_marked_rented_does_not_invoke_crm_close(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id, 'rent', 'available');

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'rented',
        ])->assertOk()
            ->assertJsonMissing(['crm']);
    }

    public function test_crm_close_recorded_in_audit_log(): void
    {
        if (! Schema::hasTable('crm_requests') || ! Schema::hasTable('users_api_customers_stages')) {
            $this->markTestSkipped('CRM tables not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        [$openStage] = $this->createCustomerStages($user->id);

        CrmRequest::create([
            'user_id' => $user->id,
            'stage_id' => $openStage->id,
            'customer_id' => $this->createCustomer($user->id)->id,
            'property_id' => $property->id,
            'position' => 0,
        ]);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
        ])->assertOk();

        $log = PropertyLog::where('property_id', $property->id)
            ->where('action', 'status_change')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('crm_close', $log->changes);
        $this->assertSame(1, $log->changes['crm_close']['closed_requests'] ?? null);
    }

    public function test_sold_closes_crm_requests_from_property_crm_relations(): void
    {
        if (! Schema::hasTable('property_crm_relations') || ! Schema::hasTable('crm_requests')) {
            $this->markTestSkipped('property_crm_relations or crm_requests not available.');
        }

        if (! Schema::hasTable('users_api_customers_stages')) {
            $this->markTestSkipped('users_api_customers_stages table not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        [$openStage, $closedStage] = $this->createCustomerStages($user->id);

        $request = CrmRequest::create([
            'user_id' => $user->id,
            'stage_id' => $openStage->id,
            'customer_id' => $this->createCustomer($user->id)->id,
            'property_id' => null,
            'position' => 0,
        ]);

        PropertyCrmRelation::create([
            'property_id' => $property->id,
            'request_id' => $request->id,
            'relation_type' => PropertyCrmRelation::TYPE_MANUALLY_ADDED,
            'occurred_at' => now(),
        ]);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
        ])->assertOk()
            ->assertJsonPath('data.crm.closed_requests', 1);

        $this->assertSame($closedStage->id, (int) CrmRequest::find($request->id)->stage_id);
    }

    private function actingAsTenant(): User
    {
        $user = User::factory()->create(['account_type' => 'tenant']);
        Sanctum::actingAs($user);

        return $user;
    }

    private function createProperty(
        int $userId,
        string $listingPurpose = 'sale',
        string $unitStatus = 'available'
    ): Property {
        return Property::create([
            'user_id' => $userId,
            'price' => 1,
            'purpose' => $listingPurpose,
            'listing_purpose' => $listingPurpose,
            'unit_status' => $unitStatus,
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'x.jpg',
            'property_type' => 'apartment',
        ]);
    }

    /**
     * @return array{0: UserApiCustomerStage, 1: UserApiCustomerStage}
     */
    private function createCustomerStages(int $userId): array
    {
        $openStage = UserApiCustomerStage::create([
            'user_id' => $userId,
            'stage_name' => 'New lead',
            'order' => 1,
            'is_active' => true,
        ]);

        $closedStage = UserApiCustomerStage::create([
            'user_id' => $userId,
            'stage_name' => 'Closing deal',
            'order' => 3,
            'is_active' => true,
        ]);

        return [$openStage, $closedStage];
    }

    private function createCustomer(int $userId, array $overrides = []): ApiCustomer
    {
        return ApiCustomer::create(array_merge([
            'user_id' => $userId,
            'name' => 'Test Customer',
            'phone_number' => '+9665' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
        ], $overrides));
    }

    private function createPropertyRequest(int $userId, array $overrides = []): int
    {
        $defaults = [
            'user_id' => $userId,
            'full_name' => 'Test Customer',
            'phone' => '+966501234567',
            'is_active' => 1,
            'is_read' => 0,
            'is_archived' => 0,
            'source' => 'whatsapp',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('users_property_requests', 'status_id')) {
            $statusId = DB::table('property_request_statuses')->where('is_active', true)->value('id');
            if ($statusId) {
                $defaults['status_id'] = $statusId;
            }
        }

        return (int) DB::table('users_property_requests')->insertGetId(array_merge($defaults, $overrides));
    }
}

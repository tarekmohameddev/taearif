<?php

namespace Tests\Feature\Crm;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\Api\UserApiCustomerStage;

class CrmRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function createStageFor(User $user): UserApiCustomerStage
    {
        return UserApiCustomerStage::create([
            'user_id'    => $user->id,
            'stage_name' => 'New',
            'order'      => 1,
            'is_active'  => true,
        ]);
    }

    public function test_create_request_with_property_id(): void
    {
        $tenant = User::factory()->create();
        Sanctum::actingAs($tenant);
        $stage = $this->createStageFor($tenant);

        $res = $this->postJson('/api/v1/crm/requests', [
            'stage_id' => $stage->id,
            'customer_name' => 'John Doe',
            'customer_phone' => '+966500000000',
            'property_id' => 123,
        ]);

        $res->assertCreated()->assertJsonPath('status', true);
        $this->assertDatabaseHas('crm_requests', [
            'user_id' => $tenant->id,
            'stage_id' => $stage->id,
            'customer_name' => 'John Doe',
            'property_id' => 123,
        ]);
    }

    public function test_create_request_with_property_specifications(): void
    {
        $tenant = User::factory()->create();
        Sanctum::actingAs($tenant);
        $stage = $this->createStageFor($tenant);

        $specs = [
            'basic_information' => [
                'address' => '123 Main Street',
                'price' => 0,
            ],
            'details' => ['features' => ['balcony']],
            'attributes' => ['area_sqft' => 1200],
            'facilities' => ['bedrooms' => 3, 'bathrooms' => 2],
        ];

        $res = $this->postJson('/api/v1/crm/requests', [
            'stage_id' => $stage->id,
            'customer_name' => 'Jane Smith',
            'customer_phone' => '+966511111111',
            'property_specifications' => $specs,
        ]);

        $res->assertCreated()->assertJsonPath('status', true);
        $this->assertDatabaseHas('crm_requests', [
            'user_id' => $tenant->id,
            'stage_id' => $stage->id,
            'customer_name' => 'Jane Smith',
        ]);
    }

    public function test_change_stage_and_reorder(): void
    {
        $tenant = User::factory()->create();
        Sanctum::actingAs($tenant);
        $stageA = $this->createStageFor($tenant);
        $stageB = UserApiCustomerStage::create([
            'user_id' => $tenant->id,
            'stage_name' => 'Review',
            'order' => 2,
            'is_active' => true,
        ]);

        $req1 = $this->postJson('/api/v1/crm/requests', [
            'stage_id' => $stageA->id,
            'customer_name' => 'A',
            'customer_phone' => '1',
            'property_id' => 1,
        ])->json('data');

        $req2 = $this->postJson('/api/v1/crm/requests', [
            'stage_id' => $stageA->id,
            'customer_name' => 'B',
            'customer_phone' => '2',
            'property_id' => 2,
        ])->json('data');

        // Move req1 to stageB
        $this->postJson("/api/v1/crm/requests/{$req1['id']}/change-stage", [
            'stage_id' => $stageB->id,
        ])->assertOk();

        // Reorder within stageA (only req2 remains, order trivial)
        $this->postJson('/api/v1/crm/requests/reorder', [
            'stage_id' => $stageA->id,
            'order' => [$req2['id']],
        ])->assertOk();
    }

    public function test_create_card_for_request(): void
    {
        $tenant = User::factory()->create();
        Sanctum::actingAs($tenant);
        $stage = $this->createStageFor($tenant);

        $req = $this->postJson('/api/v1/crm/requests', [
            'stage_id' => $stage->id,
            'customer_name' => 'C',
            'customer_phone' => '3',
            'property_id' => 11,
        ])->json('data');

        $card = $this->postJson('/api/v1/crm/cards', [
            'card_request_id' => $req['id'],
            'card_procedure' => 'note',
            'card_content' => 'Initial discussion',
        ]);

        $card->assertCreated()->assertJsonPath('status', true);
    }
}



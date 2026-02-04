<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerType;
use App\Models\ApiCustomer;
use App\Models\User;

/**
 * E2E: Customer & CRM Stage Change flow.
 * Login → POST customers → GET crm → POST change-stage → GET customers/{id}.
 */
class CustomerCrmStageChangeTest extends ApiE2ETestCase
{
    private function ensureCrmSetup(User $user): array
    {
        $type = UserApiCustomerType::firstOrCreate(
            ['user_id' => $user->id, 'value' => 'lead'],
            ['name' => 'Lead', 'order' => 1, 'icon' => '', 'color' => '#ccc']
        );
        $stage1 = UserApiCustomerStage::firstOrCreate(
            ['user_id' => $user->id, 'stage_name' => 'New'],
            ['order' => 1, 'icon' => '', 'color' => '#ccc']
        );
        $stage2 = UserApiCustomerStage::firstOrCreate(
            ['user_id' => $user->id, 'stage_name' => 'Contacted'],
            ['order' => 2, 'icon' => '', 'color' => '#ccc']
        );
        return ['type' => $type, 'stage1' => $stage1, 'stage2' => $stage2];
    }

    /** @test */
    public function full_journey_create_customer_crm_change_stage(): void
    {
        $this->fakeRecaptcha();
        $user = User::factory()->create(['account_type' => 'tenant']);
        $this->ensureCrmSetup($user);

        $login = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $user->email,
            'password' => 'password',
        ]);
        $login->assertOk();
        $token = $login->json('token');
        $headers = ['Authorization' => 'Bearer ' . $token];

        $type = UserApiCustomerType::where('user_id', $user->id)->first();
        $stage1 = UserApiCustomerStage::where('user_id', $user->id)->orderBy('order')->first();
        $stage2 = UserApiCustomerStage::where('user_id', $user->id)->orderBy('order')->skip(1)->first();
        $this->assertNotNull($type);
        $this->assertNotNull($stage1);

        // 1. Create customer
        $create = $this->withHeader('Authorization', $headers['Authorization'])
            ->postJson('/api/customers', [
                'name' => 'E2E Customer',
                'phone_number' => '+966501112222',
                'email' => 'e2e-customer@example.com',
                'type_id' => $type->id,
                'stage_id' => $stage1->id,
            ]);

        $create->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'data' => ['id']]);

        $customerId = $create->json('data.id');

        // 2. GET crm
        $crm = $this->withHeader('Authorization', $headers['Authorization'])
            ->getJson('/api/crm');

        $crm->assertOk()
            ->assertJsonPath('status', 'success');

        // 3. Change stage (if we have a second stage)
        if ($stage2) {
            $changeStage = $this->withHeader('Authorization', $headers['Authorization'])
                ->postJson('/api/crm/customers/' . $customerId . '/change-stage', [
                    'stage_id' => $stage2->id,
                ]);
            $changeStage->assertOk();
        }

        // 4. GET customer
        $show = $this->withHeader('Authorization', $headers['Authorization'])
            ->getJson('/api/customers/' . $customerId);

        $show->assertOk()
            ->assertJsonPath('status', 'success');
        $this->assertArrayHasKey('data', $show->json());
    }
}

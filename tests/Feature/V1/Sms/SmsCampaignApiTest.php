<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Models\SmsCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SmsCampaignApiTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['sms_campaigns', 'sms_templates'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function can_list_campaigns_with_tenant_scope_and_filters(): void
    {
        $this->requireTables();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();

        SmsCampaign::create([
            'user_id' => $tenantA->id,
            'name' => 'Campaign A',
            'message' => 'Hello',
            'status' => 'draft',
        ]);
        SmsCampaign::create([
            'user_id' => $tenantB->id,
            'name' => 'Campaign B',
            'message' => 'World',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenantA);
        $res = $this->getJson('/api/v1/sms/campaigns')->assertOk()->json('data.data');
        $this->assertCount(1, $res);
        $this->assertSame('Campaign A', $res[0]['name']);
    }

    /** @test */
    public function can_create_show_update_delete_campaign_with_tenant_scope(): void
    {
        $this->requireTables();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        Sanctum::actingAs($tenantA);

        $created = $this->postJson('/api/v1/sms/campaigns', [
            'name' => 'New Campaign',
            'message' => 'Body text',
            'description' => 'Optional',
        ])->assertStatus(201)->json('data.id');

        $this->getJson('/api/v1/sms/campaigns/' . $created)->assertOk()
            ->assertJsonPath('data.name', 'New Campaign');

        $this->patchJson('/api/v1/sms/campaigns/' . $created, [
            'name' => 'Updated Name',
        ])->assertOk();

        Sanctum::actingAs($tenantB);
        $this->getJson('/api/v1/sms/campaigns/' . $created)->assertStatus(404);

        Sanctum::actingAs($tenantA);
        $this->deleteJson('/api/v1/sms/campaigns/' . $created)->assertOk();
        $this->getJson('/api/v1/sms/campaigns/' . $created)->assertStatus(404);
    }

    /** @test */
    public function show_returns_404_with_campaign_not_found_code(): void
    {
        $this->requireTables();
        Sanctum::actingAs($this->createTenant());
        $this->getJson('/api/v1/sms/campaigns/99999')->assertStatus(404)
            ->assertJsonPath('code', 'CAMPAIGN_NOT_FOUND');
    }
}

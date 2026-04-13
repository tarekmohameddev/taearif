<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StagesOverridesTest extends TestCase
{
    use DatabaseTransactions;

    private function requireStagesTables(): void
    {
        if (!Schema::hasTable('customers_hub_stages')) {
            $this->markTestSkipped('customers_hub_stages table required.');
        }
        if (!Schema::hasTable('customers_hub_stage_overrides')) {
            $this->markTestSkipped('customers_hub_stage_overrides table required.');
        }
        if (!Schema::hasColumn('customers_hub_stages', 'is_system')) {
            $this->markTestSkipped('customers_hub_stages.is_system column required.');
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function tenant_can_override_system_stage_name_and_it_is_isolated(): void
    {
        $this->requireStagesTables();

        if (!DB::table('customers_hub_stages')->where('stage_id', 'new_lead')->exists()) {
            $this->markTestSkipped('new_lead stage required.');
        }

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        Sanctum::actingAs($t1);
        $this->putJson('/api/v2/customers-hub/stages/new_lead', [
            'stage_name_ar' => 'عميل جديد (ت1)',
            'color' => '#111111',
            'order' => 1,
        ])->assertOk();

        Sanctum::actingAs($t2);
        $this->putJson('/api/v2/customers-hub/stages/new_lead', [
            'stage_name_ar' => 'عميل جديد (ت2)',
            'color' => '#222222',
            'order' => 1,
        ])->assertOk();

        Sanctum::actingAs($t1);
        $stages1 = $this->getJson('/api/v2/customers-hub/stages?active_only=true')->assertOk()->json('data.stages');
        $row1 = collect($stages1)->firstWhere('stage_id', 'new_lead');
        $this->assertSame('عميل جديد (ت1)', $row1['stage_name_ar'] ?? null);
        $this->assertSame('#111111', $row1['color'] ?? null);

        Sanctum::actingAs($t2);
        $stages2 = $this->getJson('/api/v2/customers-hub/stages?active_only=true')->assertOk()->json('data.stages');
        $row2 = collect($stages2)->firstWhere('stage_id', 'new_lead');
        $this->assertSame('عميل جديد (ت2)', $row2['stage_name_ar'] ?? null);
        $this->assertSame('#222222', $row2['color'] ?? null);
    }

    /** @test */
    public function tenant_cannot_delete_system_stage(): void
    {
        $this->requireStagesTables();

        if (!DB::table('customers_hub_stages')->where('stage_id', 'new_lead')->exists()) {
            $this->markTestSkipped('new_lead stage required.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->deleteJson('/api/v2/customers-hub/stages/new_lead')
            ->assertStatus(409);
    }

    /** @test */
    public function tenant_can_create_and_delete_custom_stage(): void
    {
        $this->requireStagesTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/stages', [
            'stage_name_ar' => 'مرحلة مخصصة',
            'stage_name_en' => 'Custom Stage',
            'color' => '#abcdef',
            'order' => 50,
            'description' => 'custom',
        ])->assertStatus(201);

        $stageId = $res->json('data.stage_id');
        $this->assertNotEmpty($stageId);

        $this->deleteJson('/api/v2/customers-hub/stages/' . $stageId)->assertOk();
    }
}


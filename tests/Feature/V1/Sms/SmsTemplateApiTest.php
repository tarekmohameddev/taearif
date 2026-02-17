<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SmsTemplateApiTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (!Schema::hasTable('sms_templates')) {
            $this->markTestSkipped('sms_templates table required.');
        }
    }

    /** @test */
    public function can_crud_sms_templates_with_tenant_scope(): void
    {
        $this->requireTables();

        $tenantA = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $tenantB = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);

        Sanctum::actingAs($tenantA);

        $created = $this->postJson('/api/v1/sms/templates', [
            'name' => 'T1',
            'content' => 'Hello {{name}}',
            'category' => 'promotional',
            'is_active' => true,
        ])->assertStatus(201)->json('data.id');

        $this->getJson('/api/v1/sms/templates/' . $created)->assertOk();

        $this->patchJson('/api/v1/sms/templates/' . $created, [
            'name' => 'T1-updated',
        ])->assertOk();

        Sanctum::actingAs($tenantB);
        $this->getJson('/api/v1/sms/templates/' . $created)->assertStatus(404);

        Sanctum::actingAs($tenantA);
        $this->deleteJson('/api/v1/sms/templates/' . $created)->assertOk();
    }
}


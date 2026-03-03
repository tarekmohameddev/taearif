<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Models\User;
use App\Models\WaNumber;
use App\Models\WaTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppTenantIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (! Schema::hasTable('wa_numbers') || ! Schema::hasTable('wa_templates')) {
            $this->markTestSkipped('wa_numbers and wa_templates tables required.');
        }
    }

    /** @test */
    public function tenant_cannot_see_other_tenant_numbers(): void
    {
        $this->requireTables();

        $tenantA = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $tenantB = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        WaNumber::create([
            'user_id' => $tenantA->id,
            'provider' => 'meta',
            'phone_number' => '+966501111111',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tenantB);
        $res = $this->getJson('/api/v1/whatsapp/numbers');
        $res->assertOk();
        $data = $res->json('data');
        $this->assertEmpty($data);
    }

    /** @test */
    public function tenant_cannot_see_other_tenant_templates(): void
    {
        $this->requireTables();

        $tenantA = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $tenantB = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $tpl = WaTemplate::create([
            'user_id' => $tenantA->id,
            'name' => 'OnlyA',
            'content' => 'Content',
            'category' => 'utility',
            'is_active' => true,
        ]);

        Sanctum::actingAs($tenantB);
        $this->getJson('/api/v1/whatsapp/templates/' . $tpl->id)->assertStatus(404);
    }
}

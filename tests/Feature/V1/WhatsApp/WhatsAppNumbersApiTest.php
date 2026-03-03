<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Models\User;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppNumbersApiTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (! Schema::hasTable('wa_numbers')) {
            $this->markTestSkipped('wa_numbers table required.');
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function numbers_index_returns_tenant_scoped_list(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501234567',
            'name' => 'Main',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/whatsapp/numbers');
        $res->assertOk()
            ->assertJsonPath('data.0.phone_number', '+966501234567')
            ->assertJsonPath('data.0.status', 'active');
    }

    /** @test */
    public function numbers_show_returns_404_for_other_tenant(): void
    {
        $this->requireTables();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $num = WaNumber::create([
            'user_id' => $tenantA->id,
            'provider' => 'meta',
            'phone_number' => '+966501234567',
            'name' => 'Main',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tenantB);
        $this->getJson('/api/v1/whatsapp/numbers/' . $num->id)->assertStatus(404);
    }

    /** @test */
    public function numbers_store_creates_and_returns_201(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v1/whatsapp/numbers', [
            'provider' => 'meta',
            'phone_number' => '+966507654321',
            'name' => 'Sales',
            'status' => 'active',
        ]);
        $res->assertStatus(201);
        $this->assertDatabaseHas('wa_numbers', [
            'user_id' => $tenant->id,
            'phone_number' => '+966507654321',
            'provider' => 'meta',
        ]);
    }
}

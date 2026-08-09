<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Models\User;
use App\Models\WaAiConfig;
use App\Models\WaAiExcludedPhone;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests for the AI bot number exclusion sub-resource.
 *
 * Covers:
 * - List, add, and remove excluded phones via the API
 * - Duplicate phone returns 422
 * - Cross-tenant access returns 404
 * - Phone normalization (strips +, spaces, dashes)
 */
class AiExcludedPhoneApiTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['wa_numbers', 'wa_ai_configs', 'wa_ai_excluded_phones'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    private function createNumber(User $tenant): WaNumber
    {
        return WaNumber::create([
            'user_id'      => $tenant->id,
            'provider'     => 'meta',
            'phone_number' => '+966501111222',
            'name'         => 'Test Number',
            'status'       => 'active',
        ]);
    }

    /** @test */
    public function can_list_excluded_phones_for_a_number(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = $this->createNumber($tenant);

        WaAiExcludedPhone::create(['user_id' => $tenant->id, 'wa_number_id' => $number->id, 'phone' => '966501234567']);
        WaAiExcludedPhone::create(['user_id' => $tenant->id, 'wa_number_id' => $number->id, 'phone' => '966509876543']);

        Sanctum::actingAs($tenant);

        $res = $this->getJson('/api/v1/whatsapp/ai/config/' . $number->id . '/excluded-phones');

        $res->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.phone', '966501234567')
            ->assertJsonPath('data.1.phone', '966509876543');
    }

    /** @test */
    public function can_add_an_excluded_phone(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = $this->createNumber($tenant);

        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v1/whatsapp/ai/config/' . $number->id . '/excluded-phones', [
            'phone' => '966501234567',
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.phone', '966501234567')
            ->assertJsonPath('data.wa_number_id', $number->id);

        $this->assertDatabaseHas('wa_ai_excluded_phones', [
            'wa_number_id' => $number->id,
            'user_id'      => $tenant->id,
            'phone'        => '966501234567',
        ]);
    }

    /** @test */
    public function phone_is_normalized_on_store(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = $this->createNumber($tenant);

        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v1/whatsapp/ai/config/' . $number->id . '/excluded-phones', [
            'phone' => '+966 50 1234567',
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.phone', '966501234567');

        $this->assertDatabaseHas('wa_ai_excluded_phones', [
            'wa_number_id' => $number->id,
            'phone'        => '966501234567',
        ]);
    }

    /** @test */
    public function adding_duplicate_phone_returns_422(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = $this->createNumber($tenant);

        WaAiExcludedPhone::create(['user_id' => $tenant->id, 'wa_number_id' => $number->id, 'phone' => '966501234567']);

        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v1/whatsapp/ai/config/' . $number->id . '/excluded-phones', [
            'phone' => '966501234567',
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('code', 'PHONE_ALREADY_EXCLUDED');
    }

    /** @test */
    public function can_delete_an_excluded_phone(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = $this->createNumber($tenant);

        $record = WaAiExcludedPhone::create(['user_id' => $tenant->id, 'wa_number_id' => $number->id, 'phone' => '966501234567']);

        Sanctum::actingAs($tenant);

        $res = $this->deleteJson('/api/v1/whatsapp/ai/config/' . $number->id . '/excluded-phones/' . $record->id);

        $res->assertNoContent();

        $this->assertDatabaseMissing('wa_ai_excluded_phones', ['id' => $record->id]);
    }

    /** @test */
    public function cannot_manage_excluded_phones_for_another_tenants_number(): void
    {
        $this->requireTables();

        $ownerTenant = $this->createTenant();
        $otherTenant = $this->createTenant();
        $number      = $this->createNumber($ownerTenant);

        Sanctum::actingAs($otherTenant);

        $this->getJson('/api/v1/whatsapp/ai/config/' . $number->id . '/excluded-phones')
            ->assertNotFound();

        $this->postJson('/api/v1/whatsapp/ai/config/' . $number->id . '/excluded-phones', ['phone' => '966501234567'])
            ->assertNotFound();

        $this->deleteJson('/api/v1/whatsapp/ai/config/' . $number->id . '/excluded-phones/1')
            ->assertNotFound();
    }

    /** @test */
    public function excluded_phones_appear_in_get_config_response(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = $this->createNumber($tenant);

        WaAiConfig::updateOrCreate(
            ['user_id' => $tenant->id, 'wa_number_id' => $number->id],
            ['enabled' => true, 'scenarios' => []]
        );
        WaAiExcludedPhone::create(['user_id' => $tenant->id, 'wa_number_id' => $number->id, 'phone' => '966501234567']);

        Sanctum::actingAs($tenant);

        $res = $this->getJson('/api/v1/whatsapp/ai/config/' . $number->id);

        $res->assertOk();
        $this->assertArrayHasKey('excluded_phones', $res->json('data.data'));
        $this->assertSame('966501234567', $res->json('data.data.excluded_phones.0.phone'));
    }
}

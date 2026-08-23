<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsappAI;

use App\Models\User;
use App\Models\WaAiConfig;
use App\Models\WaAiExcludedPhone;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests for the AI bot number exclusion guard.
 *
 * Uses the sandbox simulate endpoint (POST /api/v1/whatsapp/ai/bot/simulate)
 * so the full BotOrchestrator / Employee pipeline is exercised via the sandbox
 * path without real LLM calls.
 *
 * Covers:
 * - Bot skips turn with 'excluded_number' when customer is in the exclusion list
 * - Bot processes normally when customer is NOT in the exclusion list
 */
class BotExcludedNumberTest extends TestCase
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
        return User::factory()->create([
            'account_type'   => 'tenant',
            'tenant_id'      => null,
            'rbac_version'   => (int) config('rbac.version', 1),
            'rbac_seeded_at' => now(),
        ]);
    }

    private function createNumberWithConfig(User $tenant): WaNumber
    {
        $number = WaNumber::create([
            'user_id'      => $tenant->id,
            'provider'     => 'meta',
            'phone_number' => '+966501111222',
            'name'         => 'Exclusion Test Number',
            'status'       => 'active',
        ]);

        WaAiConfig::create([
            'user_id'        => $tenant->id,
            'wa_number_id'   => $number->id,
            'enabled'        => true,
            'autonomy_level' => 'autonomous',
            'scenarios'      => [],
        ]);

        return $number;
    }

    /** @test */
    public function bot_skips_turn_for_excluded_customer_phone(): void
    {
        $this->requireTables();

        $tenant       = $this->createTenant();
        $number       = $this->createNumberWithConfig($tenant);
        $customerPhone = '966501234567';

        WaAiExcludedPhone::create([
            'user_id'      => $tenant->id,
            'wa_number_id' => $number->id,
            'phone'        => $customerPhone,
        ]);

        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'   => $number->id,
            'customer_phone' => $customerPhone,
            'message'        => 'أريد معلومات عن شقة',
        ]);

        $res->assertOk();

        $data = $res->json();
        $this->assertSame('skipped', $data['outcome']);
        $this->assertSame('excluded_number', $data['reason']);
    }

    /** @test */
    public function bot_processes_turn_for_non_excluded_phone(): void
    {
        $this->requireTables();

        $tenant       = $this->createTenant();
        $number       = $this->createNumberWithConfig($tenant);
        $excludedPhone = '966501111111';
        $otherPhone    = '966509999999';

        // Exclude a different phone — the test customer must NOT be skipped
        WaAiExcludedPhone::create([
            'user_id'      => $tenant->id,
            'wa_number_id' => $number->id,
            'phone'        => $excludedPhone,
        ]);

        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'   => $number->id,
            'customer_phone' => $otherPhone,
            'message'        => 'أريد معلومات عن شقة',
        ]);

        $res->assertOk();

        $data = $res->json();
        $this->assertNotSame('skipped', $data['outcome'] ?? '');
    }
}

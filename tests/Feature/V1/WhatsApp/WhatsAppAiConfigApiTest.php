<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Models\User;
use App\Models\WaAiConfig;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppAiConfigApiTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (! Schema::hasTable('wa_numbers') || ! Schema::hasTable('wa_ai_configs')) {
            $this->markTestSkipped('wa_numbers and wa_ai_configs tables required.');
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function update_persists_extended_ai_config_fields(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501111222',
            'name' => 'AI Number',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tenant);

        $businessHours = [
            'sunday' => ['open' => true, 'from' => '09:00', 'to' => '21:00'],
            'friday' => ['open' => false],
        ];

        $res = $this->putJson('/api/v1/whatsapp/ai/config/' . $number->id, [
            'enabled' => true,
            'autonomy_level' => 'autonomous',
            'goal' => 'salesman',
            'assistant_name' => 'نورة',
            'disclose_as_assistant' => false,
            'reply_length_target' => 470,
            'confidence_threshold' => 30,
            'groundedness_threshold' => 25,
            'business_hours_only' => false,
            'business_hours' => $businessHours,
            'timezone' => 'Asia/Riyadh',
            'scenarios' => [
                'initial_greeting' => false,
                'faq_responses' => false,
            ],
            'tone' => 'friendly',
            'language' => 'ar',
            'custom_instructions' => 'خلي كل ردودك باللهجة السعودية',
            'fallback_to_human' => false,
            'fallback_delay' => 0,
        ]);

        $res->assertOk()
            ->assertJsonPath('data.data.reply_length_target', 470)
            ->assertJsonPath('data.data.confidence_threshold', 30)
            ->assertJsonPath('data.data.groundedness_threshold', 25)
            ->assertJsonPath('data.data.disclose_as_assistant', false)
            ->assertJsonPath('data.data.goal', 'salesman')
            ->assertJsonPath('data.data.autonomy_level', 'autonomous')
            ->assertJsonPath('data.data.assistant_name', 'نورة')
            ->assertJsonPath('data.data.business_hours.sunday.open', true)
            ->assertJsonPath('data.data.business_hours.friday.open', false);

        $this->assertDatabaseHas('wa_ai_configs', [
            'user_id' => $tenant->id,
            'wa_number_id' => $number->id,
            'reply_length_target' => 470,
            'confidence_threshold' => 30,
            'groundedness_threshold' => 25,
            'disclose_as_assistant' => 0,
            'goal' => 'salesman',
            'autonomy_level' => 'autonomous',
        ]);

        $config = WaAiConfig::where('wa_number_id', $number->id)->first();
        $this->assertSame($businessHours, $config->business_hours);
    }

    /** @test */
    public function create_defaults_scenarios_when_omitted(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501111000',
            'name' => 'AI Number No Scenarios',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tenant);

        // Mirrors production UI payloads that omit scenarios on first save
        $res = $this->putJson('/api/v1/whatsapp/ai/config/' . $number->id, [
            'enabled' => true,
            'autonomy_level' => 'autonomous',
            'goal' => 'salesman',
            'timezone' => 'Asia/Riyadh',
            'tone' => 'friendly',
            'language' => 'ar',
        ]);

        $res->assertOk()
            ->assertJsonPath('data.data.enabled', true)
            ->assertJsonPath('data.data.autonomy_level', 'autonomous');

        $config = WaAiConfig::where('wa_number_id', $number->id)->first();
        $this->assertNotNull($config);
        $this->assertSame([], $config->scenarios);
    }

    /** @test */
    public function agent_reply_pause_defaults_to_48h(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501112222',
            'name' => 'AI Number 2',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tenant);

        $res = $this->putJson('/api/v1/whatsapp/ai/config/' . $number->id, [
            'enabled' => true,
            'autonomy_level' => 'autonomous',
        ]);

        $res->assertOk();

        $this->assertDatabaseHas('wa_ai_configs', [
            'wa_number_id' => $number->id,
            'agent_reply_pause' => '48h',
        ]);
    }

    /** @test */
    public function agent_reply_pause_can_be_set_to_valid_values(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501113333',
            'name' => 'AI Number 3',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tenant);

        $this->putJson('/api/v1/whatsapp/ai/config/' . $number->id, [
            'agent_reply_pause' => '48h',
        ])->assertOk();

        foreach (['off', '24h', '48h', 'indefinite'] as $mode) {
            $res = $this->putJson('/api/v1/whatsapp/ai/config/' . $number->id, [
                'agent_reply_pause' => $mode,
            ]);

            $res->assertOk();
            $this->assertDatabaseHas('wa_ai_configs', [
                'wa_number_id' => $number->id,
                'agent_reply_pause' => $mode,
            ]);
        }
    }

    /** @test */
    public function agent_reply_pause_rejects_invalid_value(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $number = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501114444',
            'name' => 'AI Number 4',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tenant);

        $res = $this->putJson('/api/v1/whatsapp/ai/config/' . $number->id, [
            'agent_reply_pause' => '72h',
        ]);

        $res->assertUnprocessable();
    }
}

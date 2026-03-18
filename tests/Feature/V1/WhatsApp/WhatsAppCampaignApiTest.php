<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Models\Api\marketing\MarketingChannelPricing;
use App\Models\Api\marketing\UserCredit;
use App\Models\User;
use App\Models\WaCampaign;
use App\Models\WaMessageLog;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppCampaignApiTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['wa_campaigns', 'wa_message_logs', 'wa_numbers', 'idempotency_keys', 'user_credits'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function requireWhatsAppPricing(): void
    {
        if (! Schema::hasTable('marketing_channel_pricing')) {
            $this->markTestSkipped('marketing_channel_pricing table required.');
        }
        MarketingChannelPricing::updateOrCreate(
            ['channel_type' => 'whatsapp'],
            [
                'credits_per_message' => 1,
                'price_per_credit' => 0.05,
                'effective_price_per_message' => 0.05,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'WhatsApp (test)',
            ]
        );
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    private function createWaNumber(User $tenant, string $status = 'active'): WaNumber
    {
        return WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501234567',
            'name' => 'Main',
            'status' => $status,
        ]);
    }

    /** @test */
    public function can_list_campaigns_with_tenant_scope_and_filters(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $waNumberA = $this->createWaNumber($tenantA);
        $this->createWaNumber($tenantB);

        WaCampaign::create([
            'user_id' => $tenantA->id,
            'wa_number_id' => $waNumberA->id,
            'name' => 'Campaign A',
            'message' => 'Hello',
            'status' => 'draft',
        ]);
        WaCampaign::create([
            'user_id' => $tenantB->id,
            'wa_number_id' => WaNumber::where('user_id', $tenantB->id)->first()->id,
            'name' => 'Campaign B',
            'message' => 'World',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenantA);
        $res = $this->getJson('/api/v1/whatsapp/campaigns')->assertOk()->json('data.data');
        $this->assertCount(1, $res);
        $this->assertSame('Campaign A', $res[0]['name']);
    }

    /** @test */
    public function can_create_show_update_delete_campaign_with_tenant_scope(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $waNumberA = $this->createWaNumber($tenantA);
        Sanctum::actingAs($tenantA);

        $created = $this->postJson('/api/v1/whatsapp/campaigns', [
            'wa_number_id' => $waNumberA->id,
            'name' => 'New Campaign',
            'message' => 'Body text',
            'description' => 'Optional',
        ])->assertStatus(201)->json('data.id');

        $this->getJson('/api/v1/whatsapp/campaigns/'.$created)->assertOk()
            ->assertJsonPath('data.name', 'New Campaign');

        $this->patchJson('/api/v1/whatsapp/campaigns/'.$created, [
            'name' => 'Updated Name',
        ])->assertOk();

        Sanctum::actingAs($tenantB);
        $this->getJson('/api/v1/whatsapp/campaigns/'.$created)->assertStatus(404);

        Sanctum::actingAs($tenantA);
        $this->deleteJson('/api/v1/whatsapp/campaigns/'.$created)->assertOk();
        $this->getJson('/api/v1/whatsapp/campaigns/'.$created)->assertStatus(404);
    }

    /** @test */
    public function show_returns_404_with_campaign_not_found_code(): void
    {
        $this->requireTables();
        Sanctum::actingAs($this->createTenant());
        $this->getJson('/api/v1/whatsapp/campaigns/99999')->assertStatus(404)
            ->assertJsonPath('code', 'CAMPAIGN_NOT_FOUND');
    }

    /** @test */
    public function send_now_creates_logs_and_returns_202(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        Bus::fake();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = WaCampaign::create([
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'Send Now Campaign',
            'message' => 'Hello',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001', '+966500000002'],
        ], ['Idempotency-Key' => 'send-now-key-'.uniqid()]);

        $res->assertStatus(202)->assertJsonPath('data.status', 'in_progress');
        $this->assertNotEmpty($res->json('data.dispatch_reference'));

        $count = WaMessageLog::where('campaign_id', $campaign->id)->count();
        $this->assertSame(2, $count);

        $campaign->refresh();
        $this->assertSame(2, (int) $campaign->recipient_count);
        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(2, (int) ($credits->reserved_credits ?? 0));
    }

    /** @test */
    public function send_with_insufficient_credits_returns_400(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 1, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = WaCampaign::create([
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'No Credits',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001', '+966500000002'],
        ], ['Idempotency-Key' => 'insufficient-'.uniqid()])->assertStatus(400)
            ->assertJsonPath('code', 'INSUFFICIENT_CREDITS');

        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(0, (int) ($credits->used_credits ?? 0));
        $this->assertSame(0, (int) ($credits->reserved_credits ?? 0));
    }

    /** @test */
    public function send_without_valid_recipients_returns_422_with_stable_code(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = WaCampaign::create([
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'Invalid Phones',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['123', 'abc'],
        ], ['Idempotency-Key' => 'invalid-recipients-'.uniqid()]);

        $res->assertStatus(422)
            ->assertJsonPath('code', 'NO_VALID_RECIPIENTS');
    }

    /** @test */
    public function same_idempotency_key_returns_replay_response(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        Bus::fake();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = WaCampaign::create([
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'Idem Campaign',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $key = 'idem-wa-'.uniqid();
        $payload = ['manual_phones' => ['+966500000001']];

        $first = $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/send", $payload, ['Idempotency-Key' => $key]);
        $first->assertStatus(202);
        $dispatchRef = $first->json('data.dispatch_reference');

        $second = $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/send", $payload, ['Idempotency-Key' => $key]);
        $second->assertStatus(202)
            ->assertJsonPath('data.dispatch_reference', $dispatchRef);
    }

    /** @test */
    public function pause_in_progress_campaign_returns_200_and_releases_credits(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        Bus::fake();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = WaCampaign::create([
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'Pause Me',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001', '+966500000002'],
        ], ['Idempotency-Key' => 'pause-wa-'.uniqid()])->assertStatus(202);

        $campaign->refresh();
        $this->assertSame(2, (int) $campaign->reserved_credits);

        $res = $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/pause");
        $res->assertStatus(200)
            ->assertJsonPath('data.status', 'paused')
            ->assertJsonPath('data.paused_count', 2);

        $campaign->refresh();
        $this->assertSame('paused', $campaign->status);
        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(0, (int) ($credits->reserved_credits ?? 0));
    }

    /** @test */
    public function resume_paused_campaign_continue_returns_202(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        Bus::fake();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = WaCampaign::create([
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'Resume Me',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001'],
        ], ['Idempotency-Key' => 'send-resume-'.uniqid()])->assertStatus(202);
        $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/pause")->assertStatus(200);

        $res = $this->postJson("/api/v1/whatsapp/campaigns/{$campaign->id}/resume", [
            'mode' => 'continue',
        ], ['Idempotency-Key' => 'resume-continue-'.uniqid()]);
        $res->assertStatus(202)->assertJsonPath('data.status', 'in_progress')->assertJsonPath('data.mode', 'continue');
    }

    /** @test */
    public function stats_returns_tenant_scoped_aggregates(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);

        WaCampaign::create([
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'Stats Campaign',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/whatsapp/stats')->assertOk();

        $res->assertJsonPath('data.total_campaigns', 1);
        $this->assertArrayHasKey('total_sent', $res->json('data'));
        $this->assertArrayHasKey('total_delivered', $res->json('data'));
        $this->assertArrayHasKey('total_failed', $res->json('data'));
        $this->assertArrayHasKey('delivery_rate', $res->json('data'));
        $this->assertArrayHasKey('this_month_sent', $res->json('data'));
    }
}

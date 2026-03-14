<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Email;

use App\Models\Api\marketing\MarketingChannelPricing;
use App\Models\Api\marketing\UserCredit;
use App\Models\EmailCampaign;
use App\Models\EmailMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function requireEmailTables(): void
    {
        foreach (['email_campaigns', 'email_message_logs', 'idempotency_keys', 'user_credits'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function requireEmailPricing(): void
    {
        if (!Schema::hasTable('marketing_channel_pricing')) {
            $this->markTestSkipped('marketing_channel_pricing table required.');
        }
        MarketingChannelPricing::updateOrCreate(
            ['channel_type' => 'email'],
            [
                'credits_per_message' => 1,
                'price_per_credit' => 0.05,
                'effective_price_per_message' => 0.05,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'Email (test)',
            ]
        );
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    private function baseUrl(): string
    {
        return '/api/v1/email';
    }

    // --- Step 2: CRUD ---

    /** @test */
    public function test_it_can_list_campaigns(): void
    {
        $this->requireEmailTables();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();

        EmailCampaign::create([
            'user_id' => $tenantA->id,
            'name' => 'Campaign A',
            'subject' => 'Subject A',
            'body_html' => '<p>Body A</p>',
            'status' => 'draft',
        ]);
        EmailCampaign::create([
            'user_id' => $tenantB->id,
            'name' => 'Campaign B',
            'subject' => 'Subject B',
            'body_html' => '<p>Body B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenantA);
        $res = $this->getJson($this->baseUrl() . '/campaigns')->assertOk();
        $items = $res->json('data.data');
        $this->assertNotNull($items);
        $this->assertCount(1, $items);
        $this->assertSame('Campaign A', $items[0]['name']);
    }

    /** @test */
    public function test_it_can_list_campaigns_with_status_filter(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Draft One',
            'subject' => 'S1',
            'body_html' => '<p>B1</p>',
            'status' => 'draft',
        ]);
        EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Scheduled One',
            'subject' => 'S2',
            'body_html' => '<p>B2</p>',
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->getJson($this->baseUrl() . '/campaigns?status=draft')->assertOk();
        $items = $res->json('data.data');
        $this->assertCount(1, $items);
        $this->assertSame('Draft One', $items[0]['name']);
    }

    /** @test */
    public function test_it_can_show_a_campaign(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Show Me',
            'subject' => 'Subject',
            'body_html' => '<p>Body</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->getJson($this->baseUrl() . '/campaigns/' . $campaign->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Show Me')
            ->assertJsonPath('data.id', $campaign->id);
    }

    /** @test */
    public function test_it_returns_404_when_showing_a_campaign_belonging_to_another_tenant(): void
    {
        $this->requireEmailTables();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $campaign = EmailCampaign::create([
            'user_id' => $tenantA->id,
            'name' => 'Other Tenant',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenantB);
        $this->getJson($this->baseUrl() . '/campaigns/' . $campaign->id)
            ->assertStatus(404)
            ->assertJsonPath('code', 'CAMPAIGN_NOT_FOUND');
    }

    /** @test */
    public function test_it_can_store_a_campaign(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $payload = [
            'name' => 'New Campaign',
            'subject' => 'Welcome',
            'body_html' => '<p>Hello</p>',
            'description' => 'Optional',
        ];

        $res = $this->postJson($this->baseUrl() . '/campaigns', $payload)->assertStatus(201);
        $id = $res->json('data.id');
        $this->assertNotNull($id);

        $this->assertDatabaseHas('email_campaigns', [
            'id' => $id,
            'user_id' => $tenant->id,
            'name' => 'New Campaign',
            'subject' => 'Welcome',
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function test_it_can_update_a_campaign(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Original',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->patchJson($this->baseUrl() . '/campaigns/' . $campaign->id, [
            'name' => 'Updated Name',
            'subject' => 'Updated Subject',
        ])->assertOk();

        $this->assertDatabaseHas('email_campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Name',
            'subject' => 'Updated Subject',
        ]);
    }

    /** @test */
    public function test_it_validates_update_status(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Draft',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->patchJson($this->baseUrl() . '/campaigns/' . $campaign->id, [
            'status' => 'invalid_status',
        ]);
        $res->assertStatus(422);
    }

    /** @test */
    public function test_it_can_delete_a_campaign(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'To Delete',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->deleteJson($this->baseUrl() . '/campaigns/' . $campaign->id)
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('email_campaigns', ['id' => $campaign->id]);
    }

    // --- Step 3: Send ---

    /** @test */
    public function test_it_can_send_a_campaign(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Send Me',
            'subject' => 'Hi',
            'body_html' => '<p>Hello</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $key = 'send-key-' . uniqid();
        $res = $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/send', [
            'manual_emails' => ['test@example.com', 'other@example.com'],
        ], ['Idempotency-Key' => $key]);

        $res->assertStatus(202)->assertJsonPath('status', true)->assertJsonPath('data.status', 'in_progress');
        $this->assertNotEmpty($res->json('data.dispatch_reference'));

        $count = EmailMessageLog::where('campaign_id', $campaign->id)->count();
        $this->assertSame(2, $count);
    }

    /** @test */
    public function test_it_fails_to_send_without_idempotency_key(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'No Key',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/send', [
            'manual_emails' => ['test@example.com'],
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['Idempotency-Key']);
    }

    /** @test */
    public function test_it_throws_insufficient_credits_exception_when_sending(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 0,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'No Credits',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/send', [
            'manual_emails' => ['test@example.com'],
        ], ['Idempotency-Key' => 'no-credits-' . uniqid()]);

        $res->assertStatus(400)->assertJsonPath('code', 'INSUFFICIENT_CREDITS');
    }

    /** @test */
    public function test_it_throws_idempotency_conflict_when_resending_same_key(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Idempotency',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $key = 'same-key-' . uniqid();
        $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/send', [
            'manual_emails' => ['first@example.com'],
        ], ['Idempotency-Key' => $key])->assertStatus(202);

        $res = $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/send', [
            'manual_emails' => ['second@example.com'],
        ], ['Idempotency-Key' => $key]);

        $res->assertStatus(409);
        $this->assertNotEmpty($res->json('code'));
    }

    // --- Step 3: Pause ---

    /** @test */
    public function test_it_can_pause_an_active_campaign(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Pause Me',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/send', [
            'manual_emails' => ['a@example.com', 'b@example.com'],
        ], ['Idempotency-Key' => 'pause-' . uniqid()])->assertStatus(202);

        $campaign->refresh();
        $this->assertSame('in_progress', $campaign->status);

        $res = $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/pause');
        $res->assertStatus(200)
            ->assertJsonPath('data.status', 'paused')
            ->assertJsonPath('data.campaign_id', $campaign->id);

        $this->assertDatabaseHas('email_campaigns', ['id' => $campaign->id, 'status' => 'paused']);
    }

    /** @test */
    public function test_it_throws_invalid_argument_if_pausing_a_completed_campaign(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Completed',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'sent',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/pause');
        $res->assertStatus(422)->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    // --- Step 3: Resume ---

    /** @test */
    public function test_it_can_resume_a_paused_campaign(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Resume Me',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/send', [
            'manual_emails' => ['x@example.com'],
        ], ['Idempotency-Key' => 'send-resume-' . uniqid()])->assertStatus(202);
        $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/pause')->assertStatus(200);

        $campaign->refresh();
        $this->assertSame('paused', $campaign->status);

        $key = 'resume-key-' . uniqid();
        $res = $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/resume', [
            'mode' => 'continue',
        ], ['Idempotency-Key' => $key]);

        $res->assertStatus(202)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.mode', 'continue');
    }

    /** @test */
    public function test_it_handles_resume_idempotency_conflicts(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Resume Idem',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/send', [
            'manual_emails' => ['y@example.com'],
        ], ['Idempotency-Key' => 'send-resume-idem-' . uniqid()])->assertStatus(202);
        $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/pause')->assertStatus(200);

        $key = 'resume-same-' . uniqid();
        $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/resume', [
            'mode' => 'continue',
        ], ['Idempotency-Key' => $key])->assertStatus(202);

        $res = $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/resume', [
            'mode' => 'continue',
        ], ['Idempotency-Key' => $key]);

        $res->assertStatus(409);
    }

    /** @test */
    public function test_it_fails_to_send_without_recipients(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);
        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'No Recipients',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson($this->baseUrl() . '/campaigns/' . $campaign->id . '/send', [], [
            'Idempotency-Key' => 'no-recipients-' . uniqid(),
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['customer_ids']);
    }

    /** @test */
    public function test_store_validates_required_fields(): void
    {
        $this->requireEmailTables();

        Sanctum::actingAs($this->createTenant());
        $res = $this->postJson($this->baseUrl() . '/campaigns', [
            'name' => '',
            'subject' => '',
            'body_html' => '',
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name', 'subject', 'body_html']);
    }
}

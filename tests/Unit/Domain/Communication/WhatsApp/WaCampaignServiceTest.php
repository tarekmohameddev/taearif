<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WaCampaignService;
use App\Models\Api\marketing\MarketingChannelPricing;
use App\Models\Api\marketing\UserCredit;
use App\Models\User;
use App\Models\WaCampaign;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class WaCampaignServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['wa_campaigns', 'wa_numbers', 'user_credits', 'marketing_channel_pricing'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function requireWhatsAppPricing(): void
    {
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

    private function createWaNumber(User $user, string $status = 'active'): WaNumber
    {
        return WaNumber::create([
            'user_id' => $user->id,
            'provider' => 'meta',
            'phone_number' => '+966501234567',
            'name' => 'Main',
            'status' => $status,
        ]);
    }

    /** @test */
    public function create_throws_when_neither_message_nor_template(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        $service = app(WaCampaignService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('WA_CAMPAIGN_CONTENT_REQUIRED');

        $service->create($tenant->id, $tenant->id, [
            'wa_number_id' => $waNumber->id,
            'name' => 'No content',
            'message' => null,
            'template_id' => null,
        ]);
    }

    /** @test */
    public function create_throws_when_both_message_and_template(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        $service = app(WaCampaignService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('WA_CAMPAIGN_CONTENT_CONFLICT');

        $service->create($tenant->id, $tenant->id, [
            'wa_number_id' => $waNumber->id,
            'name' => 'Conflict',
            'message' => 'Hello',
            'template_id' => 1,
        ]);
    }

    /** @test */
    public function create_throws_when_wa_number_not_found(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant();
        $waNumber = $this->createWaNumber($otherTenant);
        $service = app(WaCampaignService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('WA_NUMBER_NOT_FOUND');

        $service->create($tenant->id, $tenant->id, [
            'wa_number_id' => $waNumber->id,
            'name' => 'Wrong owner',
            'message' => 'Hi',
        ]);
    }

    /** @test */
    public function create_succeeds_with_message_only(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        $service = app(WaCampaignService::class);

        $campaign = $service->create($tenant->id, $tenant->id, [
            'wa_number_id' => $waNumber->id,
            'name' => 'Plain message',
            'message' => 'Hello world',
        ]);

        $this->assertInstanceOf(WaCampaign::class, $campaign);
        $this->assertSame($tenant->id, (int) $campaign->user_id);
        $this->assertSame('Hello world', $campaign->message);
        $this->assertNull($campaign->template_id);
        $this->assertSame('draft', $campaign->status);
    }

    /** @test */
    public function delete_throws_when_not_draft_or_scheduled(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $waNumber = $this->createWaNumber($tenant);
        $campaign = WaCampaign::create([
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'In progress',
            'message' => 'Hi',
            'status' => 'in_progress',
        ]);
        $service = app(WaCampaignService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft or scheduled campaigns can be deleted.');

        $service->delete($campaign);
    }

    /** @test */
    public function list_for_user_returns_only_tenant_campaigns(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $waNumberA = $this->createWaNumber($tenantA);
        $waNumberB = $this->createWaNumber($tenantB);

        WaCampaign::create([
            'user_id' => $tenantA->id,
            'wa_number_id' => $waNumberA->id,
            'name' => 'A',
            'message' => 'Hi',
            'status' => 'draft',
        ]);
        WaCampaign::create([
            'user_id' => $tenantB->id,
            'wa_number_id' => $waNumberB->id,
            'name' => 'B',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        $service = app(WaCampaignService::class);
        $paginator = $service->listForUser($tenantA->id, [], 10);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('A', $paginator->items()[0]->name);
    }
}

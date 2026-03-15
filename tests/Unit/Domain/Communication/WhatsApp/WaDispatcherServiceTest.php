<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\WhatsApp;

use App\Domain\Communication\DTOs\ProviderDispatchResult;
use App\Domain\Communication\WhatsApp\Services\WhatsAppChannelSender;
use App\Domain\Communication\WhatsApp\Services\WaDispatcherService;
use App\Models\Api\marketing\MarketingChannelPricing;
use App\Models\Api\marketing\UserCredit;
use App\Models\User;
use App\Models\WaCampaign;
use App\Models\WaMessageLog;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class WaDispatcherServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['wa_campaigns', 'wa_message_logs', 'wa_numbers', 'user_credits'] as $table) {
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

    /** @test */
    public function dispatch_campaign_updates_pending_log_to_sent_and_consumes_reserved(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $sender = Mockery::mock(WhatsAppChannelSender::class);
        $sender->shouldReceive('send')
            ->once()
            ->andReturn(ProviderDispatchResult::success('wa-msg-1'));
        $this->app->instance(WhatsAppChannelSender::class, $sender);

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        UserCredit::getOrCreateForUser($user->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $waNumber = WaNumber::create([
            'user_id' => $user->id,
            'provider' => 'meta',
            'phone_number' => '+966501234567',
            'name' => 'Main',
            'status' => 'active',
        ]);

        $campaign = WaCampaign::create([
            'user_id' => $user->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'Disp Campaign',
            'message' => 'Hi',
            'status' => 'in_progress',
            'reserved_credits' => 1,
            'meta' => ['credits_per_message' => 1],
        ]);

        WaMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'wa_number_id' => $waNumber->id,
            'recipient_phone' => '+966500000011',
            'message' => 'Hi',
            'status' => 'pending',
        ]);

        $dispatcher = app(WaDispatcherService::class);
        $dispatcher->dispatchCampaign($campaign->id);

        $campaign->refresh();
        $this->assertSame('sent', $campaign->status);
        $this->assertSame(1, (int) $campaign->sent_count);
        $this->assertSame(0, (int) $campaign->reserved_credits);

        $log = WaMessageLog::where('campaign_id', $campaign->id)->first();
        $this->assertSame('sent', $log->status);
        $this->assertSame('wa-msg-1', $log->gateway_message_id);
    }

    /** @test */
    public function dispatch_campaign_releases_reserved_on_send_failure(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $sender = Mockery::mock(WhatsAppChannelSender::class);
        $sender->shouldReceive('send')
            ->once()
            ->andReturn(ProviderDispatchResult::failure(false, 'ERR', 'provider_error'));
        $this->app->instance(WhatsAppChannelSender::class, $sender);

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        UserCredit::getOrCreateForUser($user->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 1]);

        $waNumber = WaNumber::create([
            'user_id' => $user->id,
            'provider' => 'meta',
            'phone_number' => '+966501234567',
            'name' => 'Main',
            'status' => 'active',
        ]);

        $campaign = WaCampaign::create([
            'user_id' => $user->id,
            'wa_number_id' => $waNumber->id,
            'name' => 'Fail Campaign',
            'message' => 'Hi',
            'status' => 'in_progress',
            'reserved_credits' => 1,
            'meta' => ['credits_per_message' => 1],
        ]);

        WaMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'wa_number_id' => $waNumber->id,
            'recipient_phone' => '+966500000022',
            'message' => 'Hi',
            'status' => 'pending',
        ]);

        $dispatcher = app(WaDispatcherService::class);
        $dispatcher->dispatchCampaign($campaign->id);

        $log = WaMessageLog::where('campaign_id', $campaign->id)->first();
        $this->assertSame('failed', $log->status);
        $this->assertSame('provider_error', $log->error_message);

        $campaign->refresh();
        $this->assertSame(1, (int) $campaign->failed_count);
        $this->assertSame(0, (int) $campaign->reserved_credits);
    }

    /** @test */
    public function dispatch_campaign_fail_safe_when_wa_number_ownership_mismatch(): void
    {
        $this->requireTables();
        $this->requireWhatsAppPricing();

        $userA = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $userB = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        UserCredit::getOrCreateForUser($userA->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 2]);

        $waNumberOwnedByB = WaNumber::create([
            'user_id' => $userB->id,
            'provider' => 'meta',
            'phone_number' => '+966509999999',
            'name' => 'Belongs to B',
            'status' => 'active',
        ]);

        $campaign = WaCampaign::create([
            'user_id' => $userA->id,
            'wa_number_id' => $waNumberOwnedByB->id,
            'name' => 'Orphan Campaign',
            'message' => 'Hi',
            'status' => 'in_progress',
            'reserved_credits' => 2,
            'meta' => ['credits_per_message' => 1],
        ]);

        WaMessageLog::create([
            'user_id' => $userA->id,
            'campaign_id' => $campaign->id,
            'wa_number_id' => $waNumberOwnedByB->id,
            'recipient_phone' => '+966500000033',
            'message' => 'Hi',
            'status' => 'pending',
        ]);

        $dispatcher = app(WaDispatcherService::class);
        $dispatcher->dispatchCampaign($campaign->id);

        $campaign->refresh();
        $this->assertSame('failed', $campaign->status);
        $this->assertSame(0, (int) $campaign->reserved_credits);

        $log = WaMessageLog::where('campaign_id', $campaign->id)->first();
        $this->assertSame('failed', $log->status);
    }
}

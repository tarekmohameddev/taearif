<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\WhatsApp;

use App\Domain\Communication\Exceptions\ChannelPricingNotConfiguredException;
use App\Domain\Communication\WhatsApp\Services\WaPricingResolver;
use App\Models\Api\marketing\MarketingChannelPricing;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WaPricingResolverTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['marketing_channel_pricing'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function makeRow(string $category, int $credits, bool $billable = true): void
    {
        MarketingChannelPricing::updateOrCreate(
            ['channel_type' => 'whatsapp', 'message_category' => $category],
            [
                'credits_per_message'         => $credits,
                'price_per_credit'            => 0.005,
                'effective_price_per_message' => $credits * 0.005,
                'currency'                    => 'SAR',
                'is_active'                   => true,
                'is_billable'                 => $billable,
            ]
        );
    }

    /** @test */
    public function it_returns_marketing_credits_for_marketing_template(): void
    {
        $this->requireTables();
        $this->makeRow('marketing', 10);

        $resolver = app(WaPricingResolver::class);
        $this->assertSame(10, $resolver->creditsForTemplateCategory('MARKETING'));
    }

    /** @test */
    public function it_maps_utility_template_to_utility_row(): void
    {
        $this->requireTables();
        $this->makeRow('utility', 2);

        $resolver = app(WaPricingResolver::class);
        $this->assertSame(2, $resolver->creditsForTemplateCategory('UTILITY'));
    }

    /** @test */
    public function it_maps_authentication_template(): void
    {
        $this->requireTables();
        $this->makeRow('authentication', 2);

        $resolver = app(WaPricingResolver::class);
        $this->assertSame(2, $resolver->creditsForTemplateCategory('AUTHENTICATION'));
    }

    /** @test */
    public function it_returns_marketing_credits_when_category_is_null(): void
    {
        $this->requireTables();
        $this->makeRow('marketing', 10);

        $resolver = app(WaPricingResolver::class);
        $this->assertSame(10, $resolver->creditsForTemplateCategory(null));
    }

    /** @test */
    public function it_falls_back_to_default_row_when_exact_category_missing(): void
    {
        $this->requireTables();

        // No 'utility' row — only 'default'
        MarketingChannelPricing::where('channel_type', 'whatsapp')->where('message_category', 'utility')->delete();
        $this->makeRow('default', 3);

        $resolver = app(WaPricingResolver::class);
        $this->assertSame(3, $resolver->creditsForTemplateCategory('UTILITY'));
    }

    /** @test */
    public function it_returns_zero_when_category_is_not_billable(): void
    {
        $this->requireTables();
        $this->makeRow('service', 0, false);

        $resolver = app(WaPricingResolver::class);
        $this->assertSame(0, $resolver->creditsForTemplateCategory('SERVICE'));
    }

    /** @test */
    public function it_throws_when_no_row_at_all(): void
    {
        $this->requireTables();

        MarketingChannelPricing::where('channel_type', 'whatsapp')->delete();

        $resolver = app(WaPricingResolver::class);
        $this->expectException(ChannelPricingNotConfiguredException::class);
        $resolver->creditsForTemplateCategory('MARKETING');
    }

    /** @test */
    public function it_returns_ai_bot_credits(): void
    {
        $this->requireTables();
        $this->makeRow('ai_bot', 1);

        $resolver = app(WaPricingResolver::class);
        $this->assertSame(1, $resolver->creditsForAiReply());
    }

    /** @test */
    public function it_returns_false_for_is_ai_bot_billable_when_row_is_free(): void
    {
        $this->requireTables();
        $this->makeRow('ai_bot', 0, false);

        $resolver = app(WaPricingResolver::class);
        $this->assertFalse($resolver->isAiBotBillable());
    }

    /** @test */
    public function it_returns_true_for_is_ai_bot_billable_when_row_exists_and_billable(): void
    {
        $this->requireTables();
        $this->makeRow('ai_bot', 1, true);

        $resolver = app(WaPricingResolver::class);
        $this->assertTrue($resolver->isAiBotBillable());
    }

    /** @test */
    public function default_category_row_is_used_for_sms_and_email_via_get_cost_for_message_type(): void
    {
        $this->requireTables();

        // Ensure SMS has a 'default' category row
        MarketingChannelPricing::updateOrCreate(
            ['channel_type' => 'sms', 'message_category' => 'default'],
            [
                'credits_per_message'         => 5,
                'price_per_credit'            => 0.01,
                'effective_price_per_message' => 0.05,
                'currency'                    => 'SAR',
                'is_active'                   => true,
                'is_billable'                 => true,
            ]
        );

        // Old single-arg call still works (falls back to 'default' category)
        $credits = \App\Models\Api\marketing\UserCredit::getCostForMessageType('sms');
        $this->assertSame(5, $credits);
    }
}

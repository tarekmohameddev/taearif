<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Api\marketing\MarketingChannelPricing;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreditManagementCategoryPricingTest extends TestCase
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

    private function actAsAdmin()
    {
        $admin = Admin::first();
        if (! $admin) {
            $this->markTestSkipped('No admin found in database.');
        }
        return $this->actingAs($admin, 'admin');
    }

    /** @test */
    public function admin_can_create_two_categories_for_same_channel(): void
    {
        $this->requireTables();

        // Clear existing whatsapp rows to avoid unique constraint conflicts
        MarketingChannelPricing::where('channel_type', 'whatsapp')->delete();

        $this->actAsAdmin()
            ->postJson(route('admin.credit-management.pricing.quick-create'), [
                'channel_type'        => 'whatsapp',
                'message_category'    => 'marketing',
                'credits_per_message' => 10,
                'price_per_credit'    => 0.005,
                'currency'            => 'SAR',
                'is_billable'         => 1,
            ])
            ->assertJson(['status' => 'success']);

        $this->actAsAdmin()
            ->postJson(route('admin.credit-management.pricing.quick-create'), [
                'channel_type'        => 'whatsapp',
                'message_category'    => 'utility',
                'credits_per_message' => 2,
                'price_per_credit'    => 0.005,
                'currency'            => 'SAR',
                'is_billable'         => 1,
            ])
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('marketing_channel_pricing', [
            'channel_type'     => 'whatsapp',
            'message_category' => 'marketing',
        ]);
        $this->assertDatabaseHas('marketing_channel_pricing', [
            'channel_type'     => 'whatsapp',
            'message_category' => 'utility',
        ]);
    }

    /** @test */
    public function admin_cannot_have_duplicate_channel_category_pair(): void
    {
        $this->requireTables();

        MarketingChannelPricing::where('channel_type', 'whatsapp')->where('message_category', 'marketing')->delete();

        // Create once
        $this->actAsAdmin()
            ->postJson(route('admin.credit-management.pricing.quick-create'), [
                'channel_type'        => 'whatsapp',
                'message_category'    => 'marketing',
                'credits_per_message' => 10,
                'price_per_credit'    => 0.005,
                'currency'            => 'SAR',
            ]);

        // Create again — should update (upsert), not fail
        $this->actAsAdmin()
            ->postJson(route('admin.credit-management.pricing.quick-create'), [
                'channel_type'        => 'whatsapp',
                'message_category'    => 'marketing',
                'credits_per_message' => 20, // changed
                'price_per_credit'    => 0.005,
                'currency'            => 'SAR',
            ])
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('marketing_channel_pricing', [
            'channel_type'        => 'whatsapp',
            'message_category'    => 'marketing',
            'credits_per_message' => 20,
        ]);
        // Only one row
        $this->assertSame(
            1,
            MarketingChannelPricing::where('channel_type', 'whatsapp')->where('message_category', 'marketing')->count()
        );
    }

    /** @test */
    public function admin_can_toggle_billable_flag(): void
    {
        $this->requireTables();

        $pricing = MarketingChannelPricing::updateOrCreate(
            ['channel_type' => 'whatsapp', 'message_category' => 'service'],
            [
                'credits_per_message'         => 0,
                'price_per_credit'            => 0,
                'effective_price_per_message' => 0,
                'currency'                    => 'SAR',
                'is_active'                   => true,
                'is_billable'                 => false,
            ]
        );

        $this->actAsAdmin()
            ->postJson(route('admin.credit-management.pricing.toggle-billable', $pricing->id))
            ->assertJson(['status' => 'success', 'is_billable' => true]);

        $this->assertTrue($pricing->fresh()->is_billable);

        // Toggle back
        $this->actAsAdmin()
            ->postJson(route('admin.credit-management.pricing.toggle-billable', $pricing->id))
            ->assertJson(['status' => 'success', 'is_billable' => false]);

        $this->assertFalse($pricing->fresh()->is_billable);
    }

    /** @test */
    public function service_category_with_zero_credits_is_accepted(): void
    {
        $this->requireTables();

        MarketingChannelPricing::where('channel_type', 'whatsapp')->where('message_category', 'service')->delete();

        $this->actAsAdmin()
            ->postJson(route('admin.credit-management.pricing.quick-create'), [
                'channel_type'        => 'whatsapp',
                'message_category'    => 'service',
                'credits_per_message' => 0,
                'price_per_credit'    => 0,
                'currency'            => 'SAR',
                'is_billable'         => 0,
            ])
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('marketing_channel_pricing', [
            'channel_type'        => 'whatsapp',
            'message_category'    => 'service',
            'credits_per_message' => 0,
            'is_billable'         => 0,
        ]);
    }
}

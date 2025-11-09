<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Analytics;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\User\Models\User;
use Illuminate\Support\Carbon;
use Tests\Feature\Admin\AdminApiTestCase;

class AnalyticsEndpointsTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    /** @test */
    public function admin_can_view_analytics_overview(): void
    {
        $this->signInAdmin();

        $this->getJson(route('admin.api.analytics.overview'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'summary',
                    'revenue',
                    'subscriptions',
                ],
            ]);
    }

    /** @test */
    public function analytics_mrr_endpoint_validates_months_parameter(): void
    {
        $this->signInAdmin();

        $this->getJson(route('admin.api.analytics.mrr', ['months' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('months');
    }

    /** @test */
    public function admin_can_view_mrr_analytics(): void
    {
        $this->signInAdmin();

        $this->getJson(route('admin.api.analytics.mrr'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'current_mrr',
                    'previous_mrr',
                    'monthly_trend',
                ],
            ]);
    }

    /** @test */
    public function admin_can_view_churn_analytics(): void
    {
        $this->signInAdmin();

        $this->getJson(route('admin.api.analytics.churn'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'customer_churn_rate',
                    'monthly_churn_trend',
                ],
            ]);
    }

    /** @test */
    public function admin_can_view_plan_performance_metrics(): void
    {
        $this->signInAdmin();

        $this->getJson(route('admin.api.analytics.plans'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'plans',
                    'total_active_plans',
                ],
            ]);
    }

    /** @test */
    public function analytics_compare_endpoint_requires_valid_parameters(): void
    {
        $this->signInAdmin();

        $this->getJson(route('admin.api.analytics.compare'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metric', 'period1_start', 'period1_end', 'period2_start', 'period2_end']);
    }

    /** @test */
    public function admin_can_export_analytics_data(): void
    {
        $this->signInAdmin();

        $this->postJson(route('admin.api.analytics.export'), [
            'type' => 'overview',
            'format' => 'json',
        ])->assertOk()
            ->assertJsonPath('data.type', 'overview')
            ->assertJsonPath('data.format', 'json');
    }

    private function seedBaselineData(): void
    {
        $plan = Plan::factory()->create([
            'price' => 120,
            'term' => 'monthly',
        ]);

        $user = User::factory()->create([
            'account_type' => 'tenant',
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'package_id' => $plan->id,
            'package_price' => 120,
            'discount' => 0,
            'price' => 120,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'manual',
            'transaction_id' => 'txn-' . uniqid(),
            'status' => 1,
            'is_trial' => false,
            'trial_days' => 0,
            'start_date' => Carbon::now()->subMonth()->toDateString(),
            'expire_date' => Carbon::now()->addMonth()->toDateString(),
        ]);
    }
}


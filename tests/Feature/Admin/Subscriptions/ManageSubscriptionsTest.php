<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Subscriptions;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\User\Models\User;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageSubscriptionsTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_subscriptions(): void
    {
        $this->signInAdmin();
        $subscription = $this->createSubscription();

        $this->getJson(route('admin.api.subscriptions.index'))
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $subscription->id)
            ->assertJsonPath('data.data.0.user.email', $subscription->user->email);
    }

    /** @test */
    public function listing_subscriptions_requires_authentication(): void
    {
        $this->createSubscription();

        $this->getJson(route('admin.api.subscriptions.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_subscription_details(): void
    {
        $this->signInAdmin();
        $subscription = $this->createSubscription();

        $this->getJson(route('admin.api.subscriptions.show', $subscription->id))
            ->assertOk()
            ->assertJsonPath('data.id', $subscription->id)
            ->assertJsonPath('data.plan.title', $subscription->package->title);
    }

    /** @test */
    public function subscription_show_returns_not_found_for_missing_record(): void
    {
        $this->signInAdmin();

        $this->getJson(route('admin.api.subscriptions.show', 999999))
            ->assertNotFound();
    }

    /** @test */
    public function admin_can_view_subscription_statistics(): void
    {
        $this->signInAdmin();
        $this->createSubscription();

        $this->getJson(route('admin.api.subscriptions.statistics'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'active',
                    'expiring_soon',
                    'not_renewed',
                    'trial_not_upgraded',
                ],
            ]);
    }

    private function createSubscription(): Subscription
    {
        $plan = Plan::factory()->create([
            'price' => 49.99,
        ]);

        $user = User::factory()->create([
            'account_type' => 'tenant',
        ]);

        return Subscription::query()->create([
            'user_id' => $user->id,
            'package_id' => $plan->id,
            'package_price' => $plan->price,
            'discount' => 0,
            'price' => $plan->price,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'manual',
            'transaction_id' => 'sub-' . uniqid(),
            'status' => 1,
            'is_trial' => false,
            'trial_days' => 0,
            'start_date' => now()->subWeek()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
        ])->fresh(['user', 'package']);
    }
}

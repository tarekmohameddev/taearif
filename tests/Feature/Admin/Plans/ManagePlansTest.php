<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Plans;

use App\Domain\Billing\Models\Plan;
use App\Models\Membership;
use App\Models\User as TenantUser;
use Tests\Feature\Admin\AdminApiTestCase;

class ManagePlansTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_plans(): void
    {
        $this->signInAdmin();

        Plan::factory()->count(2)->create();

        $response = $this->getJson(
            route('admin.api.plans.index')
        );

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    }

    /** @test */
    public function listing_plans_requires_authentication(): void
    {
        $this->getJson(route('admin.api.plans.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_create_a_plan(): void
    {
        $this->signInAdmin();

        $payload = [
            'title' => 'Gold Plan',
            'price' => 199.99,
            'is_trial' => true,
            'trial_days' => 14,
            'serial_number' => 1,
        ];

        $response = $this->postJson(
            route('admin.api.plans.store'),
            $payload
        );

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Gold Plan')
            ->assertJsonPath('data.price', 199.99);

        $this->assertDatabaseHas('packages', [
            'title' => 'Gold Plan',
            'price' => 199.99,
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_when_creating_a_plan_with_invalid_payload(): void
    {
        $this->signInAdmin();

        $this->postJson(
            route('admin.api.plans.store'),
            [
                'title' => '',
                'price' => -10,
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'price']);
    }

    /** @test */
    public function admin_can_view_a_plan(): void
    {
        $this->signInAdmin();

        $plan = Plan::factory()->create([
            'title' => 'Silver Plan',
            'price' => 89.99,
        ]);

        $response = $this->getJson(
            route('admin.api.plans.show', $plan->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $plan->id)
            ->assertJsonPath('data.title', 'Silver Plan');
    }

    /** @test */
    public function viewing_a_plan_requires_authentication(): void
    {
        $this->getJson(
            route('admin.api.plans.show', 123)
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_viewing_a_missing_plan(): void
    {
        $this->signInAdmin();

        $this->getJson(
            route('admin.api.plans.show', 999999)
        )->assertNotFound();
    }

    /** @test */
    public function admin_can_delete_a_plan_without_subscribers(): void
    {
        $this->signInAdmin();

        $plan = Plan::factory()->create();

        $response = $this->deleteJson(
            route('admin.api.plans.destroy', $plan->id)
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('packages', [
            'id' => $plan->id,
        ]);
    }

    /** @test */
    public function deleting_a_plan_with_active_subscribers_returns_error(): void
    {
        $this->signInAdmin();

        $plan = Plan::factory()->create();
        $tenant = TenantUser::factory()->create([
            'account_type' => 'tenant',
        ]);

        Membership::create([
            'user_id' => $tenant->id,
            'package_id' => $plan->id,
            'package_price' => 100,
            'price' => 100,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-PLAN',
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->subWeek()->toDateString(),
            'expire_date' => now()->addWeek()->toDateString(),
        ]);

        $this->deleteJson(
            route('admin.api.plans.destroy', $plan->id)
        )->assertStatus(400)
            ->assertJsonPath('errors.error_code', 'PLAN_HAS_SUBSCRIPTIONS');
    }

    /** @test */
    public function admin_can_toggle_plan_active_status(): void
    {
        $this->signInAdmin();

        $plan = Plan::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->postJson(
            route('admin.api.plans.active', $plan->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.status.is_active', true);

        $this->assertTrue($plan->fresh()->is_active);
    }

    /** @test */
    public function admin_can_toggle_plan_featured_status(): void
    {
        $this->signInAdmin();

        $plan = Plan::factory()->create([
            'featured' => 0,
        ]);

        $response = $this->postJson(
            route('admin.api.plans.featured', $plan->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.status.featured', true);

        $this->assertSame(1, $plan->fresh()->featured);
    }

    /** @test */
    public function toggling_plan_status_requires_authentication(): void
    {
        $plan = Plan::factory()->create();

        $this->postJson(
            route('admin.api.plans.active', $plan->id)
        )->assertUnauthorized();
    }
}


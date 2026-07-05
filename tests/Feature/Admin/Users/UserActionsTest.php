<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Plan;
use App\Domain\User\Models\UserActivityLog;
use App\Models\Membership;
use App\Models\User as TenantUser;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Mockery;
use Tests\Feature\Admin\AdminApiTestCase;

class UserActionsTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $mock = Mockery::mock(WhatsAppService::class);
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('sendMessage')->byDefault()->andReturnTrue();
        $mock->shouldReceive('sendTemplateToPhone')->byDefault()->andReturn(['success' => true]);

        $this->app->instance(WhatsAppService::class, $mock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function admin_can_send_a_whatsapp_message_to_user(): void
    {
        /** @var WhatsAppService|\Mockery\MockInterface $mock */
        $mock = $this->app->make(WhatsAppService::class);
        $mock->shouldReceive('sendMessage')
            ->once()
            ->with('+966500000000', 'Hello there!')
            ->andReturnTrue();

        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'phone' => '+966500000000',
        ]);

        $response = $this->postJson(
            route('admin.api.users.send-whatsapp', $tenant->id),
            [
                'message' => 'Hello there!',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.phone', '+966500000000')
            ->assertJsonPath('data.template', null);
    }

    /** @test */
    public function whatsapp_message_requires_valid_payload(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'phone' => '+966500000000',
        ]);

        $this->postJson(
            route('admin.api.users.send-whatsapp', $tenant->id),
            [
                'message' => '',
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function whatsapp_message_returns_error_when_user_has_no_phone(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'phone' => null,
        ]);

        $this->postJson(
            route('admin.api.users.send-whatsapp', $tenant->id),
            [
                'message' => 'Test',
            ]
        )->assertStatus(400)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('errors.error_code', 'USER_NO_PHONE');
    }

    /** @test */
    public function whatsapp_message_requires_authentication(): void
    {
        $this->postJson(
            route('admin.api.users.send-whatsapp', (string) Str::uuid()),
            [
                'message' => 'Test',
            ]
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_pause_and_resume_a_user(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'active' => true,
        ]);

        $pauseResponse = $this->postJson(
            route('admin.api.users.pause', $tenant->id),
            [
                'reason' => 'Fraudulent activity detected',
                'admin_notes' => 'Temporarily locking account.',
            ]
        );

        $pauseResponse->assertOk()
            ->assertJsonPath('data.status.active', false);

        $resumeResponse = $this->postJson(
            route('admin.api.users.resume', $tenant->id)
        );

        $resumeResponse->assertOk()
            ->assertJsonPath('data.status.active', true);
    }

    /** @test */
    public function pause_user_requires_reason(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'active' => true,
        ]);

        $this->postJson(
            route('admin.api.users.pause', $tenant->id),
            [
                'reason' => '',
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function cannot_pause_user_twice(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'active' => false,
        ]);

        $this->postJson(
            route('admin.api.users.pause', $tenant->id),
            [
                'reason' => 'Already paused',
            ]
        )->assertStatus(400)
            ->assertJsonPath('errors.error_code', 'USER_ALREADY_PAUSED');
    }

    /** @test */
    public function cannot_resume_active_user(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'active' => true,
        ]);

        $this->postJson(
            route('admin.api.users.resume', $tenant->id)
        )->assertStatus(400)
            ->assertJsonPath('errors.error_code', 'USER_ALREADY_ACTIVE');
    }

    /** @test */
    public function admin_can_toggle_user_ban_status(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'status' => 1,
        ]);

        $banResponse = $this->postJson(
            route('admin.api.users.ban', $tenant->id)
        );

        $banResponse->assertOk()
            ->assertJsonPath('data.status.status_code', 0);

        $unbanResponse = $this->postJson(
            route('admin.api.users.ban', $tenant->id)
        );

        $unbanResponse->assertOk()
            ->assertJsonPath('data.status.status_code', 1);
    }

    /** @test */
    public function admin_can_toggle_user_featured_status(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
            'featured' => 0,
        ]);

        $featuredResponse = $this->postJson(
            route('admin.api.users.featured', $tenant->id)
        );

        $featuredResponse->assertOk()
            ->assertJsonPath('data.status.featured', true);

        $unfeaturedResponse = $this->postJson(
            route('admin.api.users.featured', $tenant->id)
        );

        $unfeaturedResponse->assertOk()
            ->assertJsonPath('data.status.featured', false);
    }

    /** @test */
    public function admin_can_fetch_user_invoices(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        $plan = Plan::factory()->create([
            'is_active' => true,
        ]);

        Invoice::factory()->create([
            'user_id' => $tenant->id,
            'package_id' => $plan->id,
            'transaction_id' => 'INV-12345',
        ]);

        $response = $this->getJson(
            route('admin.api.users.invoices', $tenant->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.data.0.transaction_id', 'INV-12345');
    }

    /** @test */
    public function admin_can_view_user_activity_log(): void
    {
        $admin = $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        UserActivityLog::create([
            'user_id' => $tenant->id,
            'admin_id' => $admin->id,
            'action' => 'test_action',
            'description' => 'Performed a test action',
            'metadata' => ['foo' => 'bar'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PestTest/1.0',
            'created_at' => now(),
        ]);

        $response = $this->getJson(
            route('admin.api.users.activity', $tenant->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.data.0.action', 'test_action')
            ->assertJsonPath('data.data.0.performed_by.id', $admin->id);
    }

    /** @test */
    public function user_activity_requires_authentication(): void
    {
        $tenantUuid = (string) Str::uuid();

        $this->getJson(
            route('admin.api.users.activity', $tenantUuid)
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_change_user_plan_immediately(): void
    {
        $this->signInAdmin();

        $currentPlan = Plan::factory()->create([
            'is_active' => true,
        ]);

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        Membership::create([
            'user_id' => $tenant->id,
            'package_id' => $currentPlan->id,
            'package_price' => 99.00,
            'price' => 99.00,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-ORIGINAL',
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
        ]);

        $newPlan = Plan::factory()->create([
            'is_active' => true,
            'price' => 149.00,
        ]);

        $response = $this->postJson(
            route('admin.api.users.change-plan', $tenant->id),
            [
                'new_plan_id' => $newPlan->id,
                'change_type' => 'immediate',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->id);

        $this->assertDatabaseHas('memberships', [
            'user_id' => $tenant->id,
            'transaction_id' => 'TX-ORIGINAL',
            'expire_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertDatabaseHas('memberships', [
            'user_id' => $tenant->id,
            'package_id' => $newPlan->id,
            'payment_method' => 'admin_change',
        ]);
    }

    /** @test */
    public function immediate_plan_change_to_lifetime_uses_max_expire_date(): void
    {
        $this->signInAdmin();

        $currentPlan = Plan::factory()->create([
            'is_active' => true,
            'term' => 'monthly',
        ]);

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        Membership::create([
            'user_id' => $tenant->id,
            'package_id' => $currentPlan->id,
            'package_price' => 99.00,
            'price' => 99.00,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-BEFORE-LIFETIME',
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
        ]);

        $lifetimePlan = Plan::factory()->create([
            'is_active' => true,
            'term' => 'lifetime',
            'price' => 999.00,
        ]);

        $response = $this->postJson(
            route('admin.api.users.change-plan', $tenant->id),
            [
                'new_plan_id' => $lifetimePlan->id,
                'change_type' => 'immediate',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('memberships', [
            'user_id' => $tenant->id,
            'package_id' => $lifetimePlan->id,
            'payment_method' => 'admin_change',
            'expire_date' => Carbon::maxValue()->format('Y-m-d'),
        ]);
    }

    /** @test */
    public function admin_can_schedule_next_cycle_plan_change(): void
    {
        $this->signInAdmin();

        $currentPlan = Plan::factory()->create([
            'is_active' => true,
            'term' => 'monthly',
        ]);

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        Membership::create([
            'user_id' => $tenant->id,
            'package_id' => $currentPlan->id,
            'package_price' => 99.00,
            'price' => 99.00,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-CURRENT',
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
        ]);

        $newPlan = Plan::factory()->create([
            'is_active' => true,
            'term' => 'yearly',
            'price' => 149.00,
        ]);

        $response = $this->postJson(
            route('admin.api.users.change-plan', $tenant->id),
            [
                'new_plan_id' => $newPlan->id,
                'change_type' => 'next_cycle',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('memberships', [
            'user_id' => $tenant->id,
            'transaction_id' => 'TX-CURRENT',
            'expire_date' => now()->addMonth()->toDateString(),
        ]);

        $this->assertDatabaseHas('memberships', [
            'user_id' => $tenant->id,
            'package_id' => $newPlan->id,
            'payment_method' => 'admin_change_scheduled',
            'start_date' => now()->addMonth()->addDay()->toDateString(),
        ]);
    }

    /** @test */
    public function next_cycle_change_is_blocked_when_next_package_already_exists(): void
    {
        $this->signInAdmin();

        $currentPlan = Plan::factory()->create(['is_active' => true, 'term' => 'monthly']);
        $queuedPlan = Plan::factory()->create(['is_active' => true, 'term' => 'yearly']);
        $replacementPlan = Plan::factory()->create(['is_active' => true, 'term' => 'yearly']);

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        Membership::create([
            'user_id' => $tenant->id,
            'package_id' => $currentPlan->id,
            'price' => 99.00,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-CURRENT-2',
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
        ]);

        Membership::create([
            'user_id' => $tenant->id,
            'package_id' => $queuedPlan->id,
            'price' => 149.00,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-QUEUED',
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->addMonth()->addDay()->toDateString(),
            'expire_date' => now()->addMonths(13)->toDateString(),
        ]);

        $this->postJson(
            route('admin.api.users.change-plan', $tenant->id),
            [
                'new_plan_id' => $replacementPlan->id,
                'change_type' => 'next_cycle',
            ]
        )->assertStatus(400)
            ->assertJsonPath('errors.error_code', 'NEXT_PACKAGE_EXISTS');
    }

    /** @test */
    public function change_plan_requires_valid_payload(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        $this->postJson(
            route('admin.api.users.change-plan', $tenant->id),
            [
                'new_plan_id' => null,
                'change_type' => 'invalid',
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['new_plan_id', 'change_type']);
    }

    /** @test */
    public function change_plan_requires_authentication(): void
    {
        $this->postJson(
            route('admin.api.users.change-plan', (string) Str::uuid()),
            [
                'new_plan_id' => 1,
                'change_type' => 'immediate',
            ]
        )->assertUnauthorized();
    }

    /** @test */
    public function change_plan_returns_not_found_when_plan_is_missing(): void
    {
        $this->signInAdmin();

        $currentPlan = Plan::factory()->create(['is_active' => true]);

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        Membership::create([
            'user_id' => $tenant->id,
            'package_id' => $currentPlan->id,
            'package_price' => 49.00,
            'price' => 49.00,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-ORIGINAL',
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
        ]);

        $this->postJson(
            route('admin.api.users.change-plan', $tenant->id),
            [
                'new_plan_id' => 999999,
                'change_type' => 'immediate',
            ]
        )->assertStatus(404)
            ->assertJsonPath('errors.error_code', 'PLAN_NOT_AVAILABLE');
    }

    /** @test */
    public function change_plan_returns_error_when_user_has_no_active_subscription(): void
    {
        $this->signInAdmin();

        $currentPlan = Plan::factory()->create(['is_active' => true]);
        $newPlan = Plan::factory()->create(['is_active' => true]);

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        Membership::create([
            'user_id' => $tenant->id,
            'package_id' => $currentPlan->id,
            'package_price' => 49.00,
            'price' => 49.00,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-INACTIVE',
            'status' => 0,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
        ]);

        $this->postJson(
            route('admin.api.users.change-plan', $tenant->id),
            [
                'new_plan_id' => $newPlan->id,
                'change_type' => 'immediate',
            ]
        )->assertStatus(400)
            ->assertJsonPath('errors.error_code', 'NO_ACTIVE_SUBSCRIPTION');
    }

    /** @test */
    public function admin_can_cancel_subscription_immediately(): void
    {
        $this->signInAdmin();

        $plan = Plan::factory()->create(['is_active' => true]);

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        Membership::create([
            'user_id' => $tenant->id,
            'package_id' => $plan->id,
            'package_price' => 59.00,
            'price' => 59.00,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-ACTIVE',
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
        ]);

        $response = $this->postJson(
            route('admin.api.users.cancel-subscription', $tenant->id),
            [
                'cancel_type' => 'immediate',
                'reason' => 'Requested by tenant',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->id);

        $this->assertDatabaseHas('memberships', [
            'user_id' => $tenant->id,
            'transaction_id' => 'TX-ACTIVE',
            'status' => 0,
            'expire_date' => now()->toDateString(),
        ]);
    }

    /** @test */
    public function cancel_subscription_requires_reason(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        $this->postJson(
            route('admin.api.users.cancel-subscription', $tenant->id),
            [
                'cancel_type' => 'immediate',
                'reason' => '',
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function cancel_subscription_requires_authentication(): void
    {
        $this->postJson(
            route('admin.api.users.cancel-subscription', (string) Str::uuid()),
            [
                'cancel_type' => 'immediate',
                'reason' => 'Test',
            ]
        )->assertUnauthorized();
    }

    /** @test */
    public function cancel_subscription_returns_error_when_user_has_no_active_subscription(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        $this->postJson(
            route('admin.api.users.cancel-subscription', $tenant->id),
            [
                'cancel_type' => 'immediate',
                'reason' => 'No active sub test',
            ]
        )->assertStatus(400)
            ->assertJsonPath('errors.error_code', 'NO_ACTIVE_SUBSCRIPTION');
    }
}


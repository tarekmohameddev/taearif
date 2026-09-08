<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Api\GeneralSetting;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionAccessStateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureSupportTables();
    }

    public function test_get_user_returns_canonical_subscription_and_legacy_fields_from_same_state(): void
    {
        $tenant = User::factory()->tenant()->create(['active' => true, 'status' => 1]);
        $this->seedPackage(24, 'Paid Yearly', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $expiredPaid = $this->createMembership($tenant, 24, [
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
        ]);

        $free = $this->createMembership($tenant, 16, [
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
            'price' => 0,
            'payment_method' => 'system',
            'activation_source' => 'expiration_fallback',
            'transition_reason' => 'paid_expired',
            'previous_membership_id' => $expiredPaid->id,
        ]);

        GeneralSetting::create(['user_id' => $tenant->id, 'maintenance_mode' => 1]);
        Sanctum::actingAs($tenant);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.subscription.schema_version', 1)
            ->assertJsonPath('data.subscription.plan.id', 16)
            ->assertJsonPath('data.subscription.plan.type', 'free')
            ->assertJsonPath('data.subscription.entitlement_status', 'active')
            ->assertJsonPath('data.subscription.premium_access', false)
            ->assertJsonPath('data.subscription.transition.reason', 'paid_expired')
            ->assertJsonPath('data.subscription.transition.previous_membership_id', $expiredPaid->id)
            ->assertJsonPath('data.subscription.transition.previous_plan_type', 'paid')
            ->assertJsonPath('data.website_access.allowed', false)
            ->assertJsonPath('data.website_access.reason', 'subscription_required')
            ->assertJsonPath('data.membership.id', $free->id)
            ->assertJsonPath('data.membership.is_expired', false)
            ->assertJsonPath('data.membership.days_remaining', 365)
            ->assertJsonPath('data.membership.transition_reason', 'paid_expired')
            ->assertJsonPath('data.is_free_plan', true)
            ->assertJsonPath('data.has_active_membership', true);
    }

    public function test_get_user_info_alias_returns_same_contract(): void
    {
        $tenant = User::factory()->tenant()->create(['active' => true, 'status' => 1]);
        $this->seedPackage(26, 'Trial', 'trial', 0, 1, 7);
        $this->createMembership($tenant, 26, [
            'start_date' => now()->subDays(3)->toDateString(),
            'expire_date' => now()->toDateString(),
            'price' => 0,
            'is_trial' => 1,
            'trial_days' => 7,
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->getJson('/api/user/getUserInfo')
            ->assertOk()
            ->assertJsonPath('data.subscription.plan.type', 'trial')
            ->assertJsonPath('data.subscription.premium_access', true)
            ->assertJsonPath('data.subscription.days_remaining', 0)
            ->assertJsonPath('data.website_access.allowed', true);

        $response->assertJsonPath('data.is_free_plan', false);
    }

    public function test_employee_profile_uses_owner_subscription_state(): void
    {
        $tenant = User::factory()->tenant()->create(['active' => true, 'status' => 1]);
        $employee = User::factory()->employee()->create(['tenant_id' => $tenant->id, 'active' => true, 'status' => 1]);
        $this->seedPackage(28, 'Monthly Trial', 'monthly', 0, 1, 30);
        $this->createMembership($tenant, 28, [
            'start_date' => now()->subDay()->toDateString(),
            'expire_date' => now()->addDays(29)->toDateString(),
            'price' => 0,
            'is_trial' => 1,
            'trial_days' => 30,
        ]);

        Sanctum::actingAs($employee);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.subscription.plan.type', 'trial')
            ->assertJsonPath('data.subscription.premium_access', true)
            ->assertJsonPath('data.website_access.allowed', true);
    }

    public function test_cached_profile_is_invalidated_after_membership_upgrade(): void
    {
        $tenant = User::factory()->tenant()->create(['active' => true, 'status' => 1]);
        $this->seedPackage(16, 'Free', 'yearly', 0);
        $this->seedPackage(25, 'Paid Monthly', 'monthly', 50);
        $this->createMembership($tenant, 16, [
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
            'price' => 0,
            'payment_method' => 'system',
        ]);
        GeneralSetting::create(['user_id' => $tenant->id, 'maintenance_mode' => 1]);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.subscription.plan.type', 'free');

        app(MembershipService::class)->activateImmediateMembership($tenant, Package::findOrFail(25), [
            'payment_method' => 'arb',
            'source' => 'payment',
            'price' => 50,
        ]);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.subscription.plan.type', 'paid')
            ->assertJsonPath('data.subscription.premium_access', true)
            ->assertJsonPath('data.website_access.allowed', true)
            ->assertJsonPath('data.is_free_plan', false);
    }

    public function test_get_user_keeps_current_paid_membership_when_newer_future_membership_exists(): void
    {
        $tenant = User::factory()->tenant()->create(['active' => true, 'status' => 1]);
        $this->seedPackage(16, 'Free', 'yearly', 0);
        $this->seedPackage(24, 'Paid Yearly', 'yearly', 100);
        $this->seedPackage(25, 'Paid Monthly', 'monthly', 50);

        $currentPaid = $this->createMembership($tenant, 24, [
            'start_date' => now()->subDays(10)->toDateString(),
            'expire_date' => now()->addDays(20)->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        $futureQueued = $this->createMembership($tenant, 25, [
            'start_date' => now()->addDays(21)->toDateString(),
            'expire_date' => now()->addDays(51)->toDateString(),
            'price' => 50,
            'payment_method' => 'arb',
            'activation_source' => 'admin_change_scheduled',
            'previous_membership_id' => $currentPaid->id,
        ]);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.membership.id', $currentPaid->id)
            ->assertJsonPath('data.membership.package_id', 24)
            ->assertJsonPath('data.membership.is_free_plan', false)
            ->assertJsonPath('data.membership.previous_membership_id', null)
            ->assertJsonPath('data.has_active_membership', true)
            ->assertJsonPath('data.is_free_plan', false)
            ->assertJsonPath('data.subscription.plan.id', 24)
            ->assertJsonPath('data.subscription.plan.type', 'paid')
            ->assertJsonPath('data.subscription.entitlement_status', 'active')
            ->assertJsonPath('data.website_access.allowed', true);
    }

    public function test_get_user_keeps_latest_expired_membership_for_legacy_contract_without_fallback(): void
    {
        $tenant = User::factory()->tenant()->create(['active' => true, 'status' => 1]);
        $this->seedPackage(24, 'Paid Yearly', 'yearly', 100);

        $expiredPaid = $this->createMembership($tenant, 24, [
            'start_date' => now()->subMonths(2)->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.membership.id', $expiredPaid->id)
            ->assertJsonPath('data.membership.package_id', 24)
            ->assertJsonPath('data.membership.is_expired', true)
            ->assertJsonPath('data.membership.days_remaining', 0)
            ->assertJsonPath('data.has_active_membership', false)
            ->assertJsonPath('data.is_free_plan', false)
            ->assertJsonPath('data.subscription.plan.id', 24)
            ->assertJsonPath('data.subscription.plan.type', 'paid')
            ->assertJsonPath('data.subscription.entitlement_status', 'expired');
    }

    public function test_get_user_returns_null_legacy_membership_when_user_has_no_membership(): void
    {
        $tenant = User::factory()->tenant()->create(['active' => true, 'status' => 1]);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.membership', null)
            ->assertJsonPath('data.has_active_membership', false)
            ->assertJsonPath('data.is_free_plan', false)
            ->assertJsonPath('data.subscription.plan.id', null)
            ->assertJsonPath('data.subscription.plan.type', 'none')
            ->assertJsonPath('data.subscription.entitlement_status', 'none');
    }

    private function ensureSupportTables(): void
    {
        if (!Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('title_en')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->string('term')->nullable();
                $table->unsignedTinyInteger('is_trial')->default(0);
                $table->unsignedInteger('trial_days')->default(0);
                $table->string('status')->default('1');
                $table->unsignedInteger('video_size_limit')->default(0);
                $table->unsignedInteger('file_size_limit')->default(0);
                $table->unsignedInteger('number_of_vcards')->default(0);
                $table->unsignedInteger('project_limit_number')->default(0);
                $table->unsignedInteger('real_estate_limit_number')->default(0);
                $table->unsignedInteger('whatsapp_numbers_limit')->default(0);
                $table->unsignedInteger('employees_limit')->default(0);
                $table->text('features')->nullable();
                $table->timestamps();
            });
        }

        $this->ensurePackageColumns();

        if (!Schema::hasTable('memberships')) {
            Schema::create('memberships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('package_id')->nullable();
                $table->decimal('package_price', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
                $table->string('coupon_code')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->string('currency')->nullable();
                $table->string('currency_symbol')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->unsignedTinyInteger('is_trial')->default(0);
                $table->unsignedInteger('trial_days')->default(0);
                $table->text('receipt')->nullable();
                $table->text('transaction_details')->nullable();
                $table->text('settings')->nullable();
                $table->date('start_date')->nullable();
                $table->date('expire_date')->nullable();
                $table->unsignedTinyInteger('modified')->default(0);
                $table->unsignedBigInteger('conversation_id')->nullable();
                $table->string('activation_source')->nullable();
                $table->string('transition_reason')->nullable();
                $table->unsignedBigInteger('previous_membership_id')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureMembershipColumns();

        foreach ([
            'user_basic_settings' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('company_name')->nullable();
                $table->string('logo')->nullable();
                $table->timestamps();
            },
            'api_footer_settings' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->json('general')->nullable();
                $table->timestamps();
            },
            'call_settings' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->boolean('enabled')->default(false);
                $table->boolean('record_by_default')->default(false);
                $table->boolean('play_recording_announcement')->default(false);
                $table->unsignedInteger('max_channels')->default(1);
                $table->timestamps();
            },
            'basic_extendeds' => function (Blueprint $table) {
                $table->id();
                $table->string('base_currency_text')->nullable();
                $table->string('base_currency_symbol')->nullable();
            },
            'whatsapp_users' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->timestamps();
            },
            'whatsapp_addons' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('whatsapp_number_id')->nullable();
                $table->unsignedInteger('qty')->default(0);
                $table->string('status')->default('pending');
                $table->timestamp('expire_date')->nullable();
                $table->timestamps();
            },
            'employee_addons' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedInteger('qty')->default(0);
                $table->string('status')->default('pending');
                $table->timestamp('expire_date')->nullable();
                $table->timestamps();
            },
            'api_general_settings' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->boolean('maintenance_mode')->default(false);
                $table->timestamps();
            },
            'membership_change_logs' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('username')->nullable();
                $table->string('email')->nullable();
                $table->string('action')->nullable();
                $table->string('reason')->nullable();
                $table->string('previous_package')->nullable();
                $table->unsignedBigInteger('previous_package_id')->nullable();
                $table->string('new_package')->nullable();
                $table->unsignedBigInteger('new_package_id')->nullable();
                $table->timestamp('event_timestamp')->nullable();
                $table->timestamps();
            },
        ] as $table => $callback) {
            if (!Schema::hasTable($table)) {
                Schema::create($table, $callback);
            }
        }

        if (\App\Models\BasicExtended::query()->count() === 0) {
            \App\Models\BasicExtended::create([
                'base_currency_text' => 'SAR',
                'base_currency_symbol' => 'SAR',
            ]);
        }
    }

    private function ensurePackageColumns(): void
    {
        $columns = [
            'title_en' => fn (Blueprint $table) => $table->string('title_en')->nullable(),
            'video_size_limit' => fn (Blueprint $table) => $table->unsignedInteger('video_size_limit')->default(0),
            'file_size_limit' => fn (Blueprint $table) => $table->unsignedInteger('file_size_limit')->default(0),
            'number_of_vcards' => fn (Blueprint $table) => $table->unsignedInteger('number_of_vcards')->default(0),
            'project_limit_number' => fn (Blueprint $table) => $table->unsignedInteger('project_limit_number')->default(0),
            'real_estate_limit_number' => fn (Blueprint $table) => $table->unsignedInteger('real_estate_limit_number')->default(0),
            'whatsapp_numbers_limit' => fn (Blueprint $table) => $table->unsignedInteger('whatsapp_numbers_limit')->default(0),
            'employees_limit' => fn (Blueprint $table) => $table->unsignedInteger('employees_limit')->default(0),
            'features' => fn (Blueprint $table) => $table->text('features')->nullable(),
            'is_trial' => fn (Blueprint $table) => $table->unsignedTinyInteger('is_trial')->default(0),
            'trial_days' => fn (Blueprint $table) => $table->unsignedInteger('trial_days')->default(0),
            'status' => fn (Blueprint $table) => $table->string('status')->default('1'),
        ];

        foreach ($columns as $column => $definition) {
            if (!Schema::hasColumn('packages', $column)) {
                Schema::table('packages', $definition);
            }
        }
    }

    private function ensureMembershipColumns(): void
    {
        $columns = [
            'coupon_code' => fn (Blueprint $table) => $table->string('coupon_code')->nullable(),
            'currency' => fn (Blueprint $table) => $table->string('currency')->nullable(),
            'currency_symbol' => fn (Blueprint $table) => $table->string('currency_symbol')->nullable(),
            'receipt' => fn (Blueprint $table) => $table->text('receipt')->nullable(),
            'transaction_details' => fn (Blueprint $table) => $table->text('transaction_details')->nullable(),
            'settings' => fn (Blueprint $table) => $table->text('settings')->nullable(),
            'conversation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('conversation_id')->nullable(),
            'modified' => fn (Blueprint $table) => $table->unsignedTinyInteger('modified')->default(0),
            'activation_source' => fn (Blueprint $table) => $table->string('activation_source')->nullable(),
            'transition_reason' => fn (Blueprint $table) => $table->string('transition_reason')->nullable(),
            'previous_membership_id' => fn (Blueprint $table) => $table->unsignedBigInteger('previous_membership_id')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (!Schema::hasColumn('memberships', $column)) {
                Schema::table('memberships', $definition);
            }
        }
    }

    private function seedPackage(int $id, string $title, string $term, float $price, int $isTrial = 0, int $trialDays = 0): void
    {
        $package = Package::query()->find($id) ?? new Package();
        $package->id = $id;
        $package->title = $title;
        $package->title_en = $title;
        $package->price = $price;
        $package->term = $term;
        $package->is_trial = $isTrial;
        $package->trial_days = $trialDays;
        $package->status = '1';
        $package->features = json_encode([]);
        $package->save();
    }

    private function createMembership(User $user, int $packageId, array $overrides = []): Membership
    {
        return Membership::create(array_merge([
            'user_id' => $user->id,
            'package_id' => $packageId,
            'package_price' => 0,
            'discount' => 0,
            'price' => 0,
            'currency' => 'SAR',
            'currency_symbol' => 'SAR',
            'payment_method' => 'system',
            'transaction_id' => uniqid('test_', true),
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'settings' => '{}',
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
        ], $overrides));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\UserDowngradedToFree;
use App\Models\Api\GeneralSetting;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\Membership\MembershipAccessStateService;
use App\Services\UserPackageService;
use App\Services\WhatsAppService;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MembershipExpirationAccessStateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTables();
    }

    public function test_expired_trial_creates_single_free_fallback_with_trial_reason(): void
    {
        $user = User::factory()->tenant()->create();
        $this->seedPackage(26, 'Trial', 'trial', 0, 1, 7);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $expiredTrial = $this->createMembership($user, 26, [
            'start_date' => now()->subDays(8)->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'is_trial' => 1,
            'trial_days' => 7,
        ]);

        Event::fake([UserDowngradedToFree::class]);

        app(MembershipService::class)->handleMembershipExpiration($user);
        app(MembershipService::class)->handleMembershipExpiration($user);

        $freeRows = Membership::query()
            ->where('user_id', $user->id)
            ->where('package_id', 16)
            ->get();

        $this->assertCount(1, $freeRows);
        $this->assertSame('expiration_fallback', $freeRows->first()->activation_source);
        $this->assertSame('trial_expired', $freeRows->first()->transition_reason);
        $this->assertSame($expiredTrial->id, $freeRows->first()->previous_membership_id);
        $this->assertTrue((bool) GeneralSetting::where('user_id', $user->id)->value('maintenance_mode'));
        $this->assertStringContainsString('الفترة التجريبية', (string) $user->fresh()->message);
        Event::assertDispatchedTimes(UserDowngradedToFree::class, 1);
    }

    public function test_expired_paid_creates_paid_expired_message_and_metadata(): void
    {
        $user = User::factory()->tenant()->create();
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $expiredPaid = $this->createMembership($user, 24, [
            'start_date' => now()->subYear()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        app(MembershipService::class)->handleMembershipExpiration($user);

        $free = Membership::query()
            ->where('user_id', $user->id)
            ->where('package_id', 16)
            ->latest('id')
            ->first();

        $this->assertNotNull($free);
        $this->assertSame('paid_expired', $free->transition_reason);
        $this->assertSame($expiredPaid->id, $free->previous_membership_id);
        $this->assertStringContainsString('اشتراكك المدفوع', (string) $user->fresh()->message);
    }

    public function test_expired_paid_with_status_zero_pending_payment_still_falls_back_to_free_once(): void
    {
        $user = User::factory()->tenant()->create(['active' => true, 'status' => 1]);
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $expiredPaid = $this->createMembership($user, 24, [
            'start_date' => now()->subYear()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        $this->createMembership($user, 24, [
            'start_date' => now()->addDay()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
            'status' => 0,
        ]);

        $service = app(MembershipService::class);
        $service->handleMembershipExpiration($user);
        $service->handleMembershipExpiration($user);

        $freeMemberships = Membership::query()
            ->where('user_id', $user->id)
            ->where('package_id', 16)
            ->orderBy('id')
            ->get();

        $this->assertCount(1, $freeMemberships);
        $this->assertSame('paid_expired', $freeMemberships->first()->transition_reason);
        $this->assertSame($expiredPaid->id, $freeMemberships->first()->previous_membership_id);
        $this->assertTrue((bool) GeneralSetting::where('user_id', $user->id)->value('maintenance_mode'));
    }

    public function test_same_day_expiry_remains_active_and_does_not_fallback(): void
    {
        $user = User::factory()->tenant()->create();
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $activeUntilToday = $this->createMembership($user, 24, [
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        app(MembershipService::class)->handleMembershipExpiration($user);

        $this->assertDatabaseMissing('memberships', [
            'user_id' => $user->id,
            'package_id' => 16,
            'activation_source' => 'expiration_fallback',
        ]);
        $this->assertSame($activeUntilToday->id, Membership::query()->where('user_id', $user->id)->latest('id')->value('id'));
    }

    public function test_future_active_membership_prevents_fallback(): void
    {
        $user = User::factory()->tenant()->create();
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(25, 'Paid Monthly', 'monthly', 50);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $this->createMembership($user, 24, [
            'start_date' => now()->subYear()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        $queued = $this->createMembership($user, 25, [
            'start_date' => now()->addDay()->toDateString(),
            'expire_date' => now()->addDays(31)->toDateString(),
            'price' => 50,
            'payment_method' => 'arb',
            'status' => 1,
            'activation_source' => 'admin_change_scheduled',
        ]);

        app(MembershipService::class)->handleMembershipExpiration($user);

        $this->assertDatabaseMissing('memberships', [
            'user_id' => $user->id,
            'package_id' => 16,
            'activation_source' => 'expiration_fallback',
        ]);
        $this->assertSame('admin_change_scheduled', $queued->activation_source);
        $this->assertNull($queued->transition_reason);
    }

    public function test_queue_next_membership_sets_access_state_metadata(): void
    {
        $user = User::factory()->tenant()->create();
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(25, 'Paid Monthly', 'monthly', 50);

        $currentPaid = $this->createMembership($user, 24, [
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        $queued = app(MembershipService::class)->queueNextMembership($user, Package::findOrFail(25), [
            'payment_method' => 'admin_change_scheduled',
            'source' => 'admin_change_scheduled',
        ]);

        $this->assertSame('admin_change_scheduled', $queued->activation_source);
        $this->assertSame($currentPaid->id, $queued->previous_membership_id);
        $this->assertNull($queued->transition_reason);
        $this->assertSame(now()->addMonth()->addDay()->toDateString(), $queued->start_date);
    }

    public function test_manual_free_assignment_is_free_without_subscription_expired_reason(): void
    {
        $user = User::factory()->tenant()->create(['active' => true, 'status' => 1]);
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $this->createMembership($user, 24, [
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        $manualFree = app(MembershipService::class)->activateImmediateMembership($user, Package::findOrFail(16), [
            'payment_method' => 'admin_change',
            'source' => 'admin_change',
            'transition_reason' => 'manual_free',
        ]);

        GeneralSetting::updateOrCreate(['user_id' => $user->id], ['maintenance_mode' => 1]);

        $state = app(\App\Services\Membership\MembershipAccessStateService::class)->forUser($user);

        $this->assertSame(16, $manualFree->package_id);
        $this->assertSame('free', data_get($state, 'subscription.plan.type'));
        $this->assertSame('manual_maintenance', data_get($state, 'website_access.reason'));
    }

    public function test_expired_paid_with_inactive_free_package_skips_maintenance_and_downgrade_event(): void
    {
        $user = User::factory()->tenant()->create(['active' => true, 'status' => 1]);
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $this->createMembership($user, 24, [
            'start_date' => now()->subYear()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        Package::query()->whereKey(16)->update(['status' => '0']);
        Event::fake([UserDowngradedToFree::class]);

        app(MembershipService::class)->handleMembershipExpiration($user);

        $this->assertDatabaseMissing('memberships', [
            'user_id' => $user->id,
            'package_id' => 16,
            'activation_source' => 'expiration_fallback',
        ]);
        $this->assertFalse((bool) GeneralSetting::where('user_id', $user->id)->value('maintenance_mode'));
        Event::assertNotDispatched(UserDowngradedToFree::class);
    }

    public function test_expiration_rolls_back_free_fallback_when_maintenance_save_fails(): void
    {
        $user = User::factory()->tenant()->create();
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $this->createMembership($user, 24, [
            'start_date' => now()->subYear()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        Event::fake([UserDowngradedToFree::class]);

        $service = $this->makeExpirationFailureService('persistMaintenanceSetting');

        try {
            $service->handleMembershipExpiration($user);
            $this->fail('Expected maintenance save failure was not thrown.');
        } catch (Exception $e) {
            $this->assertSame('Simulated expiration persistence failure', $e->getMessage());
        }

        $this->assertDatabaseMissing('memberships', [
            'user_id' => $user->id,
            'package_id' => 16,
            'activation_source' => 'expiration_fallback',
        ]);
        $this->assertDatabaseMissing('api_general_settings', [
            'user_id' => $user->id,
            'maintenance_mode' => 1,
        ]);
        $this->assertNull($user->fresh()->message);
        Event::assertNotDispatched(UserDowngradedToFree::class);
    }

    public function test_expiration_rolls_back_free_fallback_when_message_save_fails(): void
    {
        $user = User::factory()->tenant()->create();
        $this->seedPackage(26, 'Trial', 'trial', 0, 1, 7);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $this->createMembership($user, 26, [
            'start_date' => now()->subDays(8)->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'is_trial' => 1,
            'trial_days' => 7,
        ]);

        Event::fake([UserDowngradedToFree::class]);

        $service = $this->makeExpirationFailureService('persistExpirationUserMessage');

        try {
            $service->handleMembershipExpiration($user);
            $this->fail('Expected message save failure was not thrown.');
        } catch (Exception $e) {
            $this->assertSame('Simulated expiration persistence failure', $e->getMessage());
        }

        $this->assertDatabaseMissing('memberships', [
            'user_id' => $user->id,
            'package_id' => 16,
            'activation_source' => 'expiration_fallback',
        ]);
        $this->assertDatabaseMissing('api_general_settings', [
            'user_id' => $user->id,
            'maintenance_mode' => 1,
        ]);
        $this->assertNull($user->fresh()->message);
        Event::assertNotDispatched(UserDowngradedToFree::class);
    }

    public function test_incomplete_historical_expiration_fallback_repairs_missing_database_state_without_duplicate_event(): void
    {
        $user = User::factory()->tenant()->create();
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $expiredPaid = $this->createMembership($user, 24, [
            'start_date' => now()->subYear()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        $fallback = $this->createMembership($user, 16, [
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
            'payment_method' => 'system',
            'activation_source' => 'expiration_fallback',
            'transition_reason' => 'paid_expired',
            'previous_membership_id' => $expiredPaid->id,
        ]);

        Event::fake([UserDowngradedToFree::class]);

        app(MembershipService::class)->handleMembershipExpiration($user);

        $this->assertSame($fallback->id, Membership::query()
            ->where('user_id', $user->id)
            ->where('package_id', 16)
            ->latest('id')
            ->value('id'));
        $this->assertTrue((bool) GeneralSetting::where('user_id', $user->id)->value('maintenance_mode'));
        $this->assertStringContainsString('اشتراكك المدفوع', (string) $user->fresh()->message);
        Event::assertNotDispatched(UserDowngradedToFree::class);
    }

    public function test_repeated_expiration_execution_creates_one_fallback_and_dispatches_one_event(): void
    {
        $user = User::factory()->tenant()->create();
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $expiredPaid = $this->createMembership($user, 24, [
            'start_date' => now()->subYear()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);

        Event::fake([UserDowngradedToFree::class]);

        $service = app(MembershipService::class);
        $service->handleMembershipExpiration($user);
        $service->handleMembershipExpiration($user);

        $fallbacks = Membership::query()
            ->where('user_id', $user->id)
            ->where('package_id', 16)
            ->where('activation_source', 'expiration_fallback')
            ->get();

        $this->assertCount(1, $fallbacks);
        $this->assertSame('paid_expired', $fallbacks->first()->transition_reason);
        $this->assertSame($expiredPaid->id, $fallbacks->first()->previous_membership_id);
        $this->assertTrue((bool) GeneralSetting::where('user_id', $user->id)->value('maintenance_mode'));
        $this->assertStringContainsString('اشتراكك المدفوع', (string) $user->fresh()->message);
        Event::assertDispatchedTimes(UserDowngradedToFree::class, 1);
    }

    private function ensureTables(): void
    {
        foreach ([
            'packages' => function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('title_en')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->string('term')->nullable();
                $table->unsignedTinyInteger('is_trial')->default(0);
                $table->unsignedInteger('trial_days')->default(0);
                $table->string('status')->default('1');
                $table->text('features')->nullable();
                $table->timestamps();
            },
            'memberships' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('package_id')->nullable();
                $table->decimal('package_price', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('price', 10, 2)->default(0);
                $table->string('currency')->nullable();
                $table->string('currency_symbol')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->unsignedTinyInteger('is_trial')->default(0);
                $table->unsignedInteger('trial_days')->default(0);
                $table->text('settings')->nullable();
                $table->date('start_date')->nullable();
                $table->date('expire_date')->nullable();
                $table->unsignedTinyInteger('modified')->default(0);
                $table->string('activation_source')->nullable();
                $table->string('transition_reason')->nullable();
                $table->unsignedBigInteger('previous_membership_id')->nullable();
                $table->timestamps();
            },
            'api_general_settings' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->boolean('maintenance_mode')->default(false);
                $table->timestamps();
            },
            'basic_settings' => function (Blueprint $table) {
                $table->id();
                $table->string('timezone')->nullable();
                $table->boolean('subscription_expired_enabled')->default(false);
            },
            'basic_extendeds' => function (Blueprint $table) {
                $table->id();
                $table->boolean('subscription_expired_email_enabled')->default(false);
                $table->string('base_currency_text')->nullable();
                $table->string('base_currency_symbol')->nullable();
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
            'user_languages' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('name')->nullable();
                $table->string('code')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('rtl')->default(false);
                $table->text('keywords')->nullable();
                $table->timestamps();
            },
        ] as $table => $callback) {
            if (!Schema::hasTable($table)) {
                Schema::create($table, $callback);
            }
        }

        if (!Schema::hasColumn('users', 'message')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('message')->nullable();
            });
        }

        $this->ensurePackageColumns();
        $this->ensureMembershipColumns();

        if (\App\Models\BasicExtended::query()->count() === 0) {
            \App\Models\BasicExtended::create([
                'subscription_expired_email_enabled' => false,
                'base_currency_text' => 'SAR',
                'base_currency_symbol' => 'SAR',
            ]);
        }
    }

    private function ensurePackageColumns(): void
    {
        $columns = [
            'title_en' => fn (Blueprint $table) => $table->string('title_en')->nullable(),
            'is_trial' => fn (Blueprint $table) => $table->unsignedTinyInteger('is_trial')->default(0),
            'trial_days' => fn (Blueprint $table) => $table->unsignedInteger('trial_days')->default(0),
            'status' => fn (Blueprint $table) => $table->string('status')->default('1'),
            'features' => fn (Blueprint $table) => $table->text('features')->nullable(),
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

    private function makeExpirationFailureService(string $method): MembershipService
    {
        $service = \Mockery::mock(MembershipService::class, [
            app(UserPackageService::class),
            app(WhatsAppService::class),
            app(MembershipAccessStateService::class),
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $service->shouldReceive($method)
            ->once()
            ->andThrow(new Exception('Simulated expiration persistence failure'));

        return $service;
    }
}

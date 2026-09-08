<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillMembershipTransitionMetadataTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureMembershipTables();
    }

    public function test_dry_run_writes_nothing(): void
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
        $free = $this->createMembership($user, 16, [
            'payment_method' => 'system',
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
        ]);

        $this->artisan('subscription:backfill-transition-metadata', ['--user-id' => $user->id])
            ->assertExitCode(0);

        $free->refresh();
        $this->assertNull($free->activation_source);
        $this->assertNull($free->transition_reason);
        $this->assertNull($free->previous_membership_id);
        $this->assertDatabaseHas('memberships', ['id' => $expiredTrial->id]);
    }

    public function test_apply_backfills_unambiguous_trial_and_paid_rows(): void
    {
        $trialUser = User::factory()->tenant()->create();
        $paidUser = User::factory()->tenant()->create();
        $this->seedPackage(26, 'Trial', 'trial', 0, 1, 7);
        $this->seedPackage(24, 'Paid', 'yearly', 100);
        $this->seedPackage(16, 'Free', 'yearly', 0);

        $trialExpired = $this->createMembership($trialUser, 26, [
            'start_date' => now()->subDays(8)->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'is_trial' => 1,
            'trial_days' => 7,
        ]);
        $trialFree = $this->createMembership($trialUser, 16, [
            'payment_method' => 'system',
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
        ]);

        $paidExpired = $this->createMembership($paidUser, 24, [
            'start_date' => now()->subYear()->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'price' => 100,
            'payment_method' => 'arb',
        ]);
        $paidFree = $this->createMembership($paidUser, 16, [
            'payment_method' => 'system',
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
        ]);

        $this->artisan('subscription:backfill-transition-metadata', ['--apply' => true])
            ->assertExitCode(0);

        $trialFree->refresh();
        $paidFree->refresh();

        $this->assertSame('expiration_fallback', $trialFree->activation_source);
        $this->assertSame('trial_expired', $trialFree->transition_reason);
        $this->assertSame($trialExpired->id, $trialFree->previous_membership_id);

        $this->assertSame('expiration_fallback', $paidFree->activation_source);
        $this->assertSame('paid_expired', $paidFree->transition_reason);
        $this->assertSame($paidExpired->id, $paidFree->previous_membership_id);
    }

    private function ensureMembershipTables(): void
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
                $table->decimal('price', 10, 2)->default(0);
                $table->string('currency')->nullable();
                $table->string('currency_symbol')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->unsignedTinyInteger('is_trial')->default(0);
                $table->unsignedInteger('trial_days')->default(0);
                $table->date('start_date')->nullable();
                $table->date('expire_date')->nullable();
                $table->string('activation_source')->nullable();
                $table->string('transition_reason')->nullable();
                $table->unsignedBigInteger('previous_membership_id')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureMembershipColumns();
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
            'settings' => fn (Blueprint $table) => $table->text('settings')->nullable(),
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
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
        ], $overrides));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Package;
use App\Models\User;
use Database\Seeders\TwoYearSubscriptionPackageSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TwoYearSubscriptionPlanTest extends TestCase
{
    use DatabaseTransactions;

    private function requirePlansTables(): void
    {
        foreach (['users', 'packages', 'memberships'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function actingTenant(): User
    {
        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);

        Sanctum::actingAs($tenant);

        return $tenant;
    }

    private function createPlan(array $overrides = []): Package
    {
        return Package::query()->create(array_merge([
            'title' => 'Test Plan',
            'title_en' => 'Test Plan EN',
            'slug' => 'test-plan-' . Str::random(6),
            'price' => 100,
            'term' => 'yearly',
            'status' => '1',
            'is_active' => true,
        ], $overrides));
    }

    /** @test */
    public function plans_endpoint_returns_active_fixed_duration_package(): void
    {
        $this->requirePlansTables();
        $this->actingTenant();

        $package = $this->createPlan([
            'title' => 'الباقة المميزة لمدة سنتين',
            'title_en' => 'Premium Two-Year Plan',
            'slug' => TwoYearSubscriptionPackageSeeder::SLUG,
            'price' => 996.00,
            'term' => 'yearly',
            'duration_months' => 24,
            'is_trial' => '0',
            'trial_days' => 0,
        ]);

        $response = $this->getJson('/api/settings/payment');

        $response->assertOk();

        $yearly = collect($response->json('plans.plans_yearly'));
        $plan = $yearly->firstWhere('id', $package->id);

        $this->assertNotNull($plan, 'fixed-duration package must be returned');
        $this->assertSame('996.00', $plan['price']);
        $this->assertSame(24, $plan['duration_months']);
        $this->assertTrue($plan['is_fixed_duration']);
        $this->assertSame('full_duration', $plan['price_scope']);
        $this->assertSame('yearly', $plan['billing_key']);
        $this->assertSame('Premium Two-Year Plan', $plan['name_en']);
    }

    /** @test */
    public function inactive_fixed_duration_package_is_omitted(): void
    {
        $this->requirePlansTables();
        $this->actingTenant();

        $this->createPlan(['term' => 'monthly', 'price' => 99]);
        $inactive = $this->createPlan([
            'term' => 'yearly',
            'price' => 996,
            'duration_months' => 24,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/settings/payment');
        $response->assertOk();

        $yearlyIds = collect($response->json('plans.plans_yearly'))->pluck('id')->all();
        $this->assertNotContains($inactive->id, $yearlyIds);
    }

    /** @test */
    public function existing_packages_expose_null_per_period_metadata(): void
    {
        $this->requirePlansTables();
        $this->actingTenant();

        $yearly = $this->createPlan(['term' => 'yearly', 'price' => 999]);
        $monthly = $this->createPlan(['term' => 'monthly', 'price' => 99]);

        $response = $this->getJson('/api/settings/payment');
        $response->assertOk();

        $yearlyPlan = collect($response->json('plans.plans_yearly'))->firstWhere('id', $yearly->id);
        $monthlyPlan = collect($response->json('plans.plans_monthly'))->firstWhere('id', $monthly->id);

        foreach ([$yearlyPlan, $monthlyPlan] as $plan) {
            $this->assertNotNull($plan);
            $this->assertNull($plan['duration_months']);
            $this->assertFalse($plan['is_fixed_duration']);
            $this->assertSame('per_period', $plan['price_scope']);
        }
    }

    /** @test */
    public function seeder_creates_package_and_invalidates_cache_making_it_visible(): void
    {
        $this->requirePlansTables();
        $this->actingTenant();

        Package::query()->forceCreate([
            'id' => TwoYearSubscriptionPackageSeeder::SOURCE_PACKAGE_ID,
            'title' => 'Premium Annual Source',
            'title_en' => 'Premium Annual Package',
            'slug' => 'source-premium-annual-' . Str::random(4),
            'price' => 999,
            'term' => 'yearly',
            'is_trial' => '1',
            'trial_days' => 360,
            'status' => '1',
            'is_active' => false,
            'number_of_vcards' => 1,
            'project_limit_number' => 5,
            'real_estate_limit_number' => 10,
            'video_size_limit' => 100,
            'file_size_limit' => 200,
            'serial_number' => 7,
            'whatsapp_numbers_limit' => 3,
            'employees_limit' => 4,
            'features' => '["sync"]',
        ]);

        Cache::put('payment_active_packages', collect([]), 3600);
        $this->assertTrue(Cache::has('payment_active_packages'));

        $this->seed(TwoYearSubscriptionPackageSeeder::class);

        $this->assertFalse(Cache::has('payment_active_packages'));

        $package = Package::where('slug', TwoYearSubscriptionPackageSeeder::SLUG)->first();
        $this->assertNotNull($package);
        $this->assertSame(996.0, (float) $package->price);
        $this->assertSame(24, (int) $package->duration_months);
        $this->assertSame('yearly', $package->term);
        $this->assertSame(5, (int) $package->project_limit_number);
        $this->assertSame(3, (int) $package->whatsapp_numbers_limit);
        $this->assertSame(['sync'], json_decode($package->features, true));

        $response = $this->getJson('/api/settings/payment');
        $response->assertOk();

        $yearly = collect($response->json('plans.plans_yearly'));
        $plan = $yearly->firstWhere('id', $package->id);

        $this->assertNotNull($plan);
        $this->assertSame('996.00', $plan['price']);
        $this->assertSame(24, $plan['duration_months']);
        $this->assertTrue($plan['is_fixed_duration']);
        $this->assertSame('full_duration', $plan['price_scope']);
    }

    /** @test */
    public function seeder_fails_with_exception_when_source_package_missing(): void
    {
        $this->requirePlansTables();

        Package::where('slug', TwoYearSubscriptionPackageSeeder::SLUG)->delete();
        Package::where('id', TwoYearSubscriptionPackageSeeder::SOURCE_PACKAGE_ID)->delete();

        $this->expectException(\RuntimeException::class);

        (new TwoYearSubscriptionPackageSeeder())->run();
    }
}
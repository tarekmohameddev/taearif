<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Dashboard;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\Role;
use App\Models\Membership;
use App\Models\User;
use App\Services\Admin\AdminDashboardMetricsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Feature\Admin\AdminApiTestCase;

class AdminDashboardMetricsServiceTest extends AdminApiTestCase
{
    protected bool $shouldResetAdminData = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureAdminViewData();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function it_classifies_active_memberships_by_package_and_counts_tenant_users_distinctly(): void
    {
        $clock = CarbonImmutable::parse('2026-09-06 10:00:00', AdminDashboardMetricsService::BUSINESS_TIMEZONE);
        Carbon::setTestNow($clock);

        $this->insertDashboardPackages();
        $admin = $this->adminWithPermissions(['Dashboard', 'Registered Users']);

        $paidOne = $this->tenant('paid-one');
        $paidTwo = $this->tenant('paid-two');
        $free = $this->tenant('free');
        $trialSeven = $this->tenant('trial-seven');
        $trialThirty = $this->tenant('trial-thirty');
        $future = $this->tenant('future');
        $expired = $this->tenant('expired');
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $paidOne->id,
        ]);

        $this->membership($paidOne, 24, '2026-01-01', '2026-12-31');
        $this->membership($paidOne, 25, '2026-09-01', '2026-09-30');
        $this->membership($paidTwo, 25, '2026-09-01', '2026-09-06');
        $this->membership($free, 16, '2026-01-01', '2026-12-31', 0);
        $this->membership($trialSeven, 26, '2026-09-01', '2026-09-07', 0, true);
        $this->membership($trialThirty, 28, '2026-09-01', '2026-09-30', 1, true);
        $this->membership($future, 24, '2026-09-07', '2027-09-06');
        $this->membership($expired, 24, '2025-09-01', '2026-09-05');
        $this->membership($employee, 24, '2026-01-01', '2026-12-31');

        $dashboard = app(AdminDashboardMetricsService::class)->build($admin, $clock);

        $this->assertSame(2, $dashboard['executiveSummary']['activePremiumUsers']);
        $this->assertSame(7, $dashboard['executiveSummary']['tenantUsers']);
        $this->assertSame(2, $dashboard['executiveSummary']['activePaidSubscriberUsers']);
        $this->assertSame(7, $dashboard['executiveSummary']['registeredTenantUsers']);
        $this->assertSame(0, $dashboard['executiveSummary']['uniqueDashboardUsersToday']);
        $this->assertSame(0, $dashboard['executiveSummary']['uniqueTenantsOpenedDashboardToday']);
        $this->assertSame(3, $dashboard['operationsSnapshot']['activePaidSubscriptions']);
        $this->assertSame(2, $dashboard['operationsSnapshot']['activeTrials']);
        $this->assertSame(1, $dashboard['operationsSnapshot']['freeUsers']);
        $this->assertSame(7, $dashboard['operationsSnapshot']['tenantRegistrationsThisMonth']);
        $this->assertArrayNotHasKey('customersTotal', $dashboard['executiveSummary']);
        $this->assertEmpty($dashboard['breakdowns']);
        $this->assertFalse($dashboard['visibility']['financial']);
    }

    /** @test */
    public function it_does_not_query_or_return_sections_the_role_cannot_view(): void
    {
        $admin = $this->adminWithPermissions(['Dashboard']);

        $dashboard = app(AdminDashboardMetricsService::class)->build(
            $admin,
            CarbonImmutable::parse('2026-09-06 10:00:00', AdminDashboardMetricsService::BUSINESS_TIMEZONE)
        );

        $this->assertEmpty($dashboard['executiveSummary']);
        $this->assertEmpty($dashboard['operationsSnapshot']);
        $this->assertEmpty($dashboard['trendCharts']);
        $this->assertEmpty($dashboard['breakdowns']);
        $this->assertEmpty($dashboard['recentActivity']);
        $this->assertSame([
            'users' => false,
            'packages' => false,
            'tenantOperations' => false,
            'financial' => false,
        ], $dashboard['visibility']);
    }

    /** @test */
    public function authorized_empty_user_and_package_sections_return_stable_zero_values(): void
    {
        $admin = $this->adminWithPermissions(['Dashboard', 'Registered Users', 'Packages']);

        $dashboard = app(AdminDashboardMetricsService::class)->build(
            $admin,
            CarbonImmutable::parse('2026-09-06 10:00:00', AdminDashboardMetricsService::BUSINESS_TIMEZONE)
        );

        $this->assertSame(0, $dashboard['executiveSummary']['activePremiumUsers']);
        $this->assertSame(0, $dashboard['executiveSummary']['tenantUsers']);
        $this->assertSame(0, $dashboard['executiveSummary']['activePaidSubscriberUsers']);
        $this->assertSame(0, $dashboard['executiveSummary']['registeredTenantUsers']);
        $this->assertSame(0, $dashboard['executiveSummary']['uniqueDashboardUsersToday']);
        $this->assertSame(0, $dashboard['executiveSummary']['uniqueTenantsOpenedDashboardToday']);
        $this->assertSame(0, $dashboard['operationsSnapshot']['activePaidSubscriptions']);
        $this->assertSame(0, $dashboard['operationsSnapshot']['activeTrials']);
        $this->assertSame(0, $dashboard['operationsSnapshot']['freeUsers']);
        $this->assertSame(0, $dashboard['operationsSnapshot']['tenantRegistrationsThisMonth']);
        $this->assertSame(0, $dashboard['operationsSnapshot']['packagesTotal']);
        $this->assertSame(0, $dashboard['operationsSnapshot']['packagesActive']);
        $this->assertTrue($dashboard['trendCharts']['tenantRegistrations']['isEmpty']);
        $this->assertSame(array_fill(0, 12, 0), $dashboard['trendCharts']['tenantRegistrations']['values']);
        $this->assertEmpty($dashboard['recentActivity']['tenants']);
        $this->assertEmpty($dashboard['breakdowns']);
    }

    /** @test */
    public function recent_tenant_signups_resolve_a_display_name_without_na_placeholders(): void
    {
        $admin = $this->adminWithPermissions(['Dashboard', 'Registered Users']);
        $settingsNameWins = $this->tenant('settings-name-wins');
        $userNameWins = $this->tenant('user-name-wins');
        $usernameFallback = $this->tenant('username-fallback');

        $settingsNameWins->update(['company_name' => 'N/A']);
        $userNameWins->update(['company_name' => 'User Company']);
        $usernameFallback->update(['company_name' => __('N/A', [], 'ar')]);

        DB::table('user_basic_settings')->insert([
            [
                'user_id' => $settingsNameWins->id,
                'company_name' => 'Settings Company',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $userNameWins->id,
                'company_name' => 'N/A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $usernameFallback->id,
                'company_name' => __('N/A', [], 'ar'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $dashboard = app(AdminDashboardMetricsService::class)->build(
            $admin,
            CarbonImmutable::parse('2026-09-06 10:00:00', AdminDashboardMetricsService::BUSINESS_TIMEZONE)
        );
        $displayNames = $dashboard['recentActivity']['tenants']->keyBy('username')->map->display_name;

        $this->assertSame('Settings Company', $displayNames['settings-name-wins']);
        $this->assertSame('User Company', $displayNames['user-name-wins']);
        $this->assertSame('username-fallback', $displayNames['username-fallback']);
    }

    /** @test */
    public function package_permission_returns_only_package_metrics(): void
    {
        $this->insertDashboardPackages();
        $admin = $this->adminWithPermissions(['Dashboard', 'Packages']);

        $dashboard = app(AdminDashboardMetricsService::class)->build(
            $admin,
            CarbonImmutable::parse('2026-09-06 10:00:00', AdminDashboardMetricsService::BUSINESS_TIMEZONE)
        );

        $this->assertEmpty($dashboard['executiveSummary']);
        $this->assertSame([
            'packagesTotal' => 5,
            'packagesActive' => 4,
        ], $dashboard['operationsSnapshot']);
        $this->assertEmpty($dashboard['trendCharts']);
        $this->assertEmpty($dashboard['breakdowns']);
        $this->assertEmpty($dashboard['recentActivity']);
    }

    /** @test */
    public function super_admin_receives_normalized_property_project_and_customer_metrics(): void
    {
        $clock = CarbonImmutable::parse('2026-09-06 10:00:00', AdminDashboardMetricsService::BUSINESS_TIMEZONE);
        Carbon::setTestNow($clock);
        Config::set('properties.backfill_complete', true);

        DB::table('user_projects')->delete();
        $tenant = $this->tenant('operations-owner');
        $admin = Admin::factory()->create(['role_id' => null, 'status' => true]);

        $this->property($tenant->id, 'sale', 'available', 'published', true);
        $this->property($tenant->id, 'rent', 'rented', 'draft', true);
        $this->property($tenant->id, null, 'sold', 'published', false);

        $this->project($tenant->id, true, true, 0);
        $this->project($tenant->id, false, false, 1);
        $this->project($tenant->id, true, false, 2);

        for ($index = 1; $index <= 6; $index++) {
            DB::table('api_customers')->insert([
                'user_id' => $tenant->id,
                'name' => 'Customer ' . $index,
                'email' => 'customer-' . $index . '@example.test',
                'phone_number' => '50000000' . $index,
                'password' => 'test',
                'created_at' => $clock->subDays($index),
                'updated_at' => $clock->subDays($index),
                'deleted_at' => $index === 6 ? $clock : null,
            ]);
        }

        $dashboard = app(AdminDashboardMetricsService::class)->build($admin, $clock);

        $this->assertSame(5, $dashboard['executiveSummary']['customersTotal']);
        $this->assertSame(2, $dashboard['executiveSummary']['publishedProperties']);
        $this->assertSame(3, $dashboard['operationsSnapshot']['propertiesTotal']);
        $this->assertSame(1, $dashboard['operationsSnapshot']['publishedFeaturedProperties']);
        $this->assertSame(3, $dashboard['operationsSnapshot']['projectsTotal']);
        $this->assertSame(2, $dashboard['operationsSnapshot']['projectsPublished']);

        $property = $dashboard['breakdowns']['properties'];
        $this->assertSame([1, 1, 1], array_column($property['listingPurpose']['items'], 'value'));
        $this->assertSame([1, 1, 1, 0], array_column($property['availability']['items'], 'value'));
        $this->assertSame([2, 1, 0], array_column($property['publication']['items'], 'value'));

        $project = $dashboard['breakdowns']['projects'];
        $this->assertSame([2, 1], array_column($project['publication']['items'], 'value'));
        $this->assertSame([1, 2], array_column($project['featured']['items'], 'value'));
        $this->assertSame([1, 1, 1, 0], array_column($project['completion']['items'], 'value'));

        $this->assertCount(5, $dashboard['recentActivity']['customers']);
        $this->assertSame(5, $dashboard['trendCharts']['customers']['total']);
        $this->assertFalse($dashboard['trendCharts']['customers']['isEmpty']);
    }

    /** @test */
    public function redesigned_dashboard_renders_safe_metrics_without_financial_claims(): void
    {
        $this->insertDashboardPackages();
        $admin = Admin::factory()->create(['role_id' => null, 'status' => true]);
        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('Executive Summary'), false);
        $response->assertSee(__('Active Paid Subscriber Users'), false);
        $response->assertSee(__('Financial Metrics'), false);
        $response->assertSee(__('Business Breakdowns'), false);
        $response->assertSee('dashboard-chart-data', false);
        $response->assertDontSee(__('Total Revenue This Year'), false);
        $response->assertDontSee(__('Monthly Revenue'), false);
    }

    /** @test */
    public function financial_metrics_are_hidden_without_financial_permission(): void
    {
        $admin = $this->adminWithPermissions(['Dashboard', 'Registered Users']);

        DB::table('user_properties')->insert([
            'user_id' => $this->tenant('priced-only')->id,
            'project_id' => 1,
            'price' => 1500.25,
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'purpose' => 'sale',
            'property_type' => 'apartment',
            'area' => 100,
            'status' => 1,
            'featured' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dashboard = app(AdminDashboardMetricsService::class)->build($admin);

        $this->assertFalse($dashboard['visibility']['financial']);
        $this->assertSame([], $dashboard['financialMetrics']);
    }

    /** @test */
    public function financial_metrics_are_exposed_with_payment_log_permission(): void
    {
        $clock = CarbonImmutable::parse('2026-09-06 10:00:00', AdminDashboardMetricsService::BUSINESS_TIMEZONE);
        Carbon::setTestNow($clock);

        $admin = $this->adminWithPermissions(['Dashboard', 'Registered Users', 'Payment Log']);
        $tenant = $this->tenant('finance-owner');

        DB::table('user_properties')->insert([
            [
                'user_id' => $tenant->id,
                'project_id' => 11,
                'price' => 1000.55,
                'listing_purpose' => 'sale',
                'unit_status' => 'available',
                'purpose' => 'sale',
                'property_type' => 'apartment',
                'area' => 100,
                'status' => 1,
                'featured' => false,
                'created_at' => $clock,
                'updated_at' => $clock,
            ],
            [
                'user_id' => $tenant->id,
                'project_id' => null,
                'price' => null,
                'listing_purpose' => 'sale',
                'unit_status' => 'available',
                'purpose' => 'sale',
                'property_type' => 'villa',
                'area' => 150,
                'status' => 1,
                'featured' => false,
                'created_at' => $clock,
                'updated_at' => $clock,
            ],
        ]);

        DB::table('sales')->insert([
            'property_id' => 1,
            'user_id' => $tenant->id,
            'sale_price' => 2500.75,
            'sale_date' => $clock,
            'status' => 'completed',
            'created_at' => $clock,
            'updated_at' => $clock,
        ]);

        DB::table('rm_rentals')->insert([
            [
                'user_id' => $tenant->id,
                'tenant_full_name' => 'SAR Tenant',
                'tenant_phone' => '500000001',
                'currency' => 'SAR',
                'total_rental_amount' => 900.50,
                'status' => 'active',
                'created_at' => $clock,
                'updated_at' => $clock,
            ],
            [
                'user_id' => $tenant->id,
                'tenant_full_name' => 'USD Tenant',
                'tenant_phone' => '500000002',
                'currency' => 'USD',
                'total_rental_amount' => 100.00,
                'status' => 'active',
                'created_at' => $clock,
                'updated_at' => $clock,
            ],
        ]);

        $dashboard = app(AdminDashboardMetricsService::class)->build($admin, $clock);

        $this->assertTrue($dashboard['visibility']['financial']);
        $this->assertSame('1000.55', $dashboard['financialMetrics']['projectInventoryValue']['amount']);
        $this->assertSame(1, $dashboard['financialMetrics']['forSaleInventoryValue']['unpricedRecords']);
        $this->assertSame('2500.75', $dashboard['financialMetrics']['completedSalesValue']['amount']);
        $this->assertSame('900.50', $dashboard['financialMetrics']['activeRentalContractValue']['amount']);
        $this->assertSame(1, $dashboard['financialMetrics']['activeRentalContractValue']['excludedNonSarRecords']);
    }

    /** @test */
    public function dashboard_financial_cards_render_amounts_without_decimal_places(): void
    {
        $clock = CarbonImmutable::parse('2026-09-06 10:00:00', AdminDashboardMetricsService::BUSINESS_TIMEZONE);
        Carbon::setTestNow($clock);

        $admin = $this->adminWithPermissions(['Dashboard', 'Registered Users', 'Payment Log']);
        $tenant = $this->tenant('finance-render-owner');
        $this->actingAs($admin, 'admin');

        DB::table('user_properties')->insert([
            [
                'user_id' => $tenant->id,
                'project_id' => 22,
                'price' => 1000.55,
                'listing_purpose' => 'sale',
                'unit_status' => 'available',
                'purpose' => 'sale',
                'property_type' => 'apartment',
                'area' => 100,
                'status' => 1,
                'featured' => false,
                'created_at' => $clock,
                'updated_at' => $clock,
            ],
            [
                'user_id' => $tenant->id,
                'project_id' => null,
                'price' => 1500.25,
                'listing_purpose' => 'sale',
                'unit_status' => 'available',
                'purpose' => 'sale',
                'property_type' => 'villa',
                'area' => 120,
                'status' => 1,
                'featured' => false,
                'created_at' => $clock,
                'updated_at' => $clock,
            ],
        ]);

        DB::table('sales')->insert([
            'property_id' => 1,
            'user_id' => $tenant->id,
            'sale_price' => 2500.75,
            'sale_date' => $clock,
            'status' => 'completed',
            'created_at' => $clock,
            'updated_at' => $clock,
        ]);

        DB::table('rm_rentals')->insert([
            [
                'user_id' => $tenant->id,
                'tenant_full_name' => 'SAR Tenant',
                'tenant_phone' => '500000101',
                'currency' => 'SAR',
                'total_rental_amount' => 900.50,
                'status' => 'active',
                'created_at' => $clock,
                'updated_at' => $clock,
            ],
            [
                'user_id' => $tenant->id,
                'tenant_full_name' => 'USD Tenant',
                'tenant_phone' => '500000102',
                'currency' => 'USD',
                'total_rental_amount' => 100.70,
                'status' => 'active',
                'created_at' => $clock,
                'updated_at' => $clock,
            ],
        ]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('USD', false);
        $response->assertSee('101', false);
        $response->assertDontSee('1,000.55 SAR', false);
        $response->assertDontSee('2,500.75 SAR', false);
        $response->assertDontSee('900.50 SAR', false);
        $response->assertDontSee('100.70', false);

        $content = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/dashboard-money-amount">\s*1,001\s*<\/span>\s*<span class="dashboard-money-currency">\s*SAR\s*<\/span>/',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/dashboard-money-amount">\s*2,501\s*<\/span>\s*<span class="dashboard-money-currency">\s*SAR\s*<\/span>/',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/dashboard-money-amount">\s*901\s*<\/span>\s*<span class="dashboard-money-currency">\s*SAR\s*<\/span>/',
            $content
        );
    }

    /** @test */
    public function dashboard_only_role_does_not_receive_super_admin_operational_metrics(): void
    {
        $admin = $this->adminWithPermissions(['Dashboard']);
        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee(__('Total Customers'), false);
        $response->assertDontSee(__('Published Properties'), false);
        $response->assertDontSee(__('Business Breakdowns'), false);
        $response->assertDontSee(__('Recent Customers'), false);
    }

    private function adminWithPermissions(array $permissions): Admin
    {
        $role = Role::factory()->create(['permissions' => $permissions]);

        return Admin::factory()->create([
            'role_id' => $role->id,
            'status' => true,
        ]);
    }

    private function tenant(string $username): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'username' => $username,
            'email' => $username . '@example.test',
        ]);
    }

    private function membership(
        User $user,
        int $packageId,
        string $startDate,
        string $expireDate,
        float $price = 99,
        bool $isTrial = false
    ): Membership {
        return Membership::query()->create([
            'package_id' => $packageId,
            'user_id' => $user->id,
            'package_price' => $price,
            'price' => $price,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => $isTrial ? 'trial' : 'test',
            'transaction_id' => uniqid('dashboard-test-', true),
            'status' => 1,
            'is_trial' => $isTrial,
            'trial_days' => 0,
            'start_date' => $startDate,
            'expire_date' => $expireDate,
        ]);
    }

    private function insertDashboardPackages(): void
    {
        foreach ([
            [16, 'Free', 'free', 'yearly', 0, false],
            [24, 'Paid yearly', 'paid-yearly', 'yearly', 999, false],
            [25, 'Paid monthly', 'paid-monthly', 'monthly', 99, false],
            [26, 'Trial seven', 'trial-seven', 'trial', 0, true],
            [28, 'Trial thirty', 'trial-thirty', 'monthly', 1, true],
        ] as [$id, $title, $slug, $term, $price, $isTrial]) {
            DB::table('packages')->insert([
                'id' => $id,
                'title' => $title,
                'slug' => $slug,
                'term' => $term,
                'price' => $price,
                'featured' => '0',
                'is_trial' => $isTrial ? '1' : '0',
                'trial_days' => 0,
                'status' => 1,
                'is_active' => $id !== 28,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function property(
        int $userId,
        ?string $listingPurpose,
        string $unitStatus,
        string $publishStatus,
        bool $featured
    ): void {
        DB::table('user_properties')->insert([
            'user_id' => $userId,
            'purpose' => $listingPurpose,
            'listing_purpose' => $listingPurpose,
            'unit_status' => $unitStatus,
            'publish_status' => $publishStatus,
            'property_type' => 'apartment',
            'area' => 100,
            'status' => $publishStatus === 'published' ? 1 : 0,
            'completion_status' => 'complete',
            'featured' => $featured,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function project(int $userId, bool $published, bool $featured, int $completeStatus): void
    {
        DB::table('user_projects')->insert([
            'user_id' => $userId,
            'published' => $published,
            'featured' => $featured,
            'complete_status' => $completeStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureAdminViewData(): void
    {
        DB::table('languages')->updateOrInsert(
            ['code' => 'en'],
            [
                'name' => 'English',
                'is_default' => 1,
                'rtl' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $languageId = (int) DB::table('languages')->where('code', 'en')->value('id');
        $settings = [
            'language_id' => $languageId,
            'website_title' => 'Taearif',
            'timezone' => AdminDashboardMetricsService::BUSINESS_TIMEZONE,
            'logo' => 'logo.png',
            'favicon' => 'favicon.png',
        ];

        if (Schema::hasColumn('basic_settings', 'copyright_text')) {
            $settings['copyright_text'] = 'Taearif';
        }

        DB::table('basic_settings')->updateOrInsert(['language_id' => $languageId], $settings);
        DB::table('basic_extendeds')->updateOrInsert(['language_id' => $languageId], []);

        $language = \App\Models\Language::query()
            ->with(['basic_setting', 'basic_extended'])
            ->where('is_default', 1)
            ->firstOrFail();

        View::share([
            'bs' => $language->basic_setting,
            'be' => $language->basic_extended,
            'currentLang' => $language,
            'menus' => json_encode([]),
            'rtl' => 0,
            'socials' => collect(),
            'langs' => \App\Models\Language::all(),
            'adminLanguages' => \App\Models\Language::orderByDesc('is_default')->get(),
            'admin_rtl' => false,
            'defaultLang' => $language,
            'adminPermissions' => [],
        ]);
    }
}

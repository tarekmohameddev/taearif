<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RegisterUser;

use App\Domain\Admin\Models\Admin;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class RegisterUserStatsTest extends AdminApiTestCase
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
    public function new_this_month_counts_from_first_of_month_until_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-08-15 12:00:00', 'UTC'));

        $this->signInWebAdmin();

        $this->createTenantUserAt('new-at-start', Carbon::parse('2025-08-01 08:00:00', 'UTC'));
        $this->createTenantUserAt('new-today', Carbon::parse('2025-08-15 09:00:00', 'UTC'));
        $this->createTenantUserAt('new-last-month', Carbon::parse('2025-07-20 10:00:00', 'UTC'));

        $response = $this->get(route('admin.register.user'));

        $response->assertOk();
        $this->assertSame(2, $this->statCount($response, 'registrations'));
    }

    /** @test */
    public function filtered_registrations_tile_follows_the_date_filter_not_the_current_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 12:00:00', 'UTC'));

        $this->signInWebAdmin();

        $this->createTenantUserAt('reg-window-may-a', Carbon::parse('2026-05-10 09:00:00', 'UTC'));
        $this->createTenantUserAt('reg-window-may-b', Carbon::parse('2026-05-20 09:00:00', 'UTC'));
        $this->createTenantUserAt('reg-window-august', Carbon::parse('2026-08-05 09:00:00', 'UTC'));

        $response = $this->get(route('admin.register.user', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]));

        $response->assertOk();

        // The selected window, not the current month ANDed onto it (which is 0).
        $this->assertSame(2, $this->statCount($response, 'registrations', 'statsFiltered'));

        // The totals row is never filtered: still the current month, globally.
        $this->assertSame(1, $this->statCount($response, 'registrations', 'statsTotal'));

        $response->assertSee('المسجلون خلال الفترة المحددة', false);
        $response->assertSee('المسجلون الجدد هذا الشهر', false);
    }

    /** @test */
    public function filtered_registrations_tile_honours_a_half_open_date_filter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 12:00:00', 'UTC'));

        $this->signInWebAdmin();

        $this->createTenantUserAt('reg-half-old', Carbon::parse('2026-03-01 09:00:00', 'UTC'));
        $this->createTenantUserAt('reg-half-mid', Carbon::parse('2026-06-01 09:00:00', 'UTC'));
        $this->createTenantUserAt('reg-half-new', Carbon::parse('2026-08-05 09:00:00', 'UTC'));

        // Only a From date: no upper bound should be invented.
        $response = $this->get(route('admin.register.user', ['start_date' => '2026-05-01']));

        $response->assertOk();
        $this->assertSame(2, $this->statCount($response, 'registrations', 'statsFiltered'));
    }

    /** @test */
    public function registrations_tile_keeps_the_month_title_when_no_date_filter_is_set(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 12:00:00', 'UTC'));

        $this->signInWebAdmin();

        $this->createTenantUserAt('reg-title-august', Carbon::parse('2026-08-05 09:00:00', 'UTC'));

        // A non-date filter must not switch the tile away from the month window.
        $response = $this->get(route('admin.register.user', ['term' => 'reg-title-august']));

        $response->assertOk();
        $this->assertSame(1, $this->statCount($response, 'registrations', 'statsFiltered'));
        $response->assertDontSee('المسجلون خلال الفترة المحددة', false);
    }

    /** @test */
    public function each_bucket_counts_its_own_users(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        $this->createTenantUserAt('bucket-new-month', now()->startOfMonth()->addHours(2));
        $this->createTenantUserAt('bucket-paid-yearly', now()->subMonths(2));
        $this->createCurrentMembership(
            User::query()->where('username', 'bucket-paid-yearly')->firstOrFail(),
            $packages[24]
        );

        $this->createTenantUserAt('bucket-paid-monthly', now()->subMonths(2));
        $this->createCurrentMembership(
            User::query()->where('username', 'bucket-paid-monthly')->firstOrFail(),
            $packages[25]
        );

        $this->createTenantUserAt('bucket-trial-7-active', now()->subMonths(2));
        $this->createCurrentMembership(
            User::query()->where('username', 'bucket-trial-7-active')->firstOrFail(),
            $packages[26]
        );

        $this->createTenantUserAt('bucket-trial-30-active', now()->subMonths(2));
        $this->createCurrentMembership(
            User::query()->where('username', 'bucket-trial-30-active')->firstOrFail(),
            $packages[28]
        );

        $trial7ExpiredUser = $this->createTenantUserAt('bucket-trial-7-expired', now()->subMonths(2));
        $this->createExpiredMembership($trial7ExpiredUser, $packages[26]);

        $trial30ExpiredUser = $this->createTenantUserAt('bucket-trial-30-expired', now()->subMonths(2));
        $this->createExpiredMembership($trial30ExpiredUser, $packages[28]);

        $this->createTenantUserAt('bucket-expired-free', now()->subMonths(2));
        $this->createCurrentMembership(
            User::query()->where('username', 'bucket-expired-free')->firstOrFail(),
            $packages[16]
        );

        $response = $this->get(route('admin.register.user'));

        $response->assertOk();
        $this->assertSame(1, $this->statCount($response, 'registrations'));
        $this->assertSame(1, $this->statCount($response, 'paid_yearly'));
        $this->assertSame(1, $this->statCount($response, 'paid_monthly'));
        $this->assertSame(1, $this->statCount($response, 'trial_7_active'));
        $this->assertSame(1, $this->statCount($response, 'trial_30_active'));
        $this->assertSame(1, $this->statCount($response, 'trial_7_expired'));
        $this->assertSame(1, $this->statCount($response, 'trial_30_expired'));
        $this->assertSame(3, $this->statCount($response, 'expired'));
    }

    /** @test */
    public function filtered_row_reacts_to_the_package_filter_button(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        $yearlyUser = $this->createTenantUser('stats-filter-yearly');
        $monthlyUser = $this->createTenantUser('stats-filter-monthly');

        $this->createCurrentMembership($yearlyUser, $packages[24]);
        $this->createCurrentMembership($monthlyUser, $packages[25]);

        $response = $this->get(route('admin.register.user', ['package_id' => 24]));

        $response->assertOk();
        $this->assertSame(1, $this->statCount($response, 'paid_yearly', 'statsFiltered'));
        $this->assertSame(0, $this->statCount($response, 'paid_monthly', 'statsFiltered'));
        $this->assertSame(1, $this->statCount($response, 'paid_yearly', 'statsTotal'));
        $this->assertSame(1, $this->statCount($response, 'paid_monthly', 'statsTotal'));
    }

    /** @test */
    public function filtered_row_reacts_to_the_search_term(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        $alphaUser = $this->createTenantUser('alpha-stats-search');
        $betaUser = $this->createTenantUser('beta-stats-search');

        $this->createCurrentMembership($alphaUser, $packages[24]);
        $this->createCurrentMembership($betaUser, $packages[25]);

        $response = $this->get(route('admin.register.user', ['term' => 'alpha-stats-search']));

        $response->assertOk();
        $this->assertSame(1, $this->statCount($response, 'paid_yearly', 'statsFiltered'));
        $this->assertSame(0, $this->statCount($response, 'paid_monthly', 'statsFiltered'));
        $this->assertSame(1, $this->statCount($response, 'paid_yearly', 'statsTotal'));
        $this->assertSame(1, $this->statCount($response, 'paid_monthly', 'statsTotal'));
    }

    /** @test */
    public function thirty_day_trial_is_not_counted_as_paid_monthly(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        $trialUser = $this->createTenantUser('stats-trial-30');
        $this->createCurrentMembership($trialUser, $packages[28]);

        $response = $this->get(route('admin.register.user'));

        $response->assertOk();
        $this->assertSame(1, $this->statCount($response, 'trial_30_active'));
        $this->assertSame(0, $this->statCount($response, 'paid_monthly'));
    }

    /** @test */
    public function corrupted_membership_is_trial_flag_does_not_change_counts(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        $yearlyUser = $this->createTenantUser('stats-corrupted-yearly');
        $this->createCurrentMembership($yearlyUser, $packages[24], [
            'is_trial' => 1,
            'trial_days' => 30,
        ]);

        $response = $this->get(route('admin.register.user'));

        $response->assertOk();
        $this->assertSame(1, $this->statCount($response, 'paid_yearly'));
        $this->assertSame(0, $this->statCount($response, 'trial_30_active'));
        $this->assertSame(0, $this->statCount($response, 'trial_7_active'));
    }

    /** @test */
    public function user_who_never_subscribed_is_not_counted_as_expired(): void
    {
        $this->signInWebAdmin();

        $this->createTenantUser('stats-never-subscribed');

        $response = $this->get(route('admin.register.user'));

        $response->assertOk();
        $this->assertSame(0, $this->statCount($response, 'expired'));
    }

    protected function signInWebAdmin(): Admin
    {
        $admin = Admin::factory()->create([
            'status' => true,
            'role_id' => null,
        ]);

        $this->actingAs($admin, 'admin');

        View::share([
            'adminUser' => $admin,
        ]);

        return $admin;
    }

    protected function createTenantUserAt(string $username, Carbon $createdAt): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'username' => $username,
            'email' => $username . '@example.test',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function statCount($response, string $key, string $row = 'statsTotal'): int
    {
        $stat = collect($response->viewData($row))->firstWhere('key', $key);

        $this->assertNotNull($stat, "Stat bucket [{$key}] missing from {$row}.");

        return (int) $stat['count'];
    }

    protected function ensureAdminViewData(): void
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

        $settingsPayload = [
            'language_id' => $languageId,
            'website_title' => 'Taearif',
            'timezone' => 'UTC',
            'logo' => 'logo.png',
            'favicon' => 'favicon.png',
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('basic_settings', 'copyright_text')) {
            $settingsPayload['copyright_text'] = 'Taearif';
        }

        DB::table('basic_settings')->updateOrInsert(
            ['language_id' => $languageId],
            $settingsPayload
        );

        DB::table('basic_extendeds')->updateOrInsert(
            ['language_id' => $languageId],
            []
        );

        $currentLang = \App\Models\Language::query()
            ->with(['basic_setting', 'basic_extended'])
            ->where('is_default', 1)
            ->firstOrFail();

        View::share([
            'bs' => $currentLang->basic_setting,
            'be' => $currentLang->basic_extended,
            'currentLang' => $currentLang,
            'menus' => json_encode([]),
            'rtl' => 0,
            'socials' => collect(),
            'langs' => \App\Models\Language::all(),
            'adminLanguages' => \App\Models\Language::orderBy('is_default', 'desc')->get(),
            'admin_rtl' => false,
            'defaultLang' => $currentLang,
            'adminPermissions' => [],
        ]);
    }

    protected function createPackageFilterPackages(): array
    {
        return [
            24 => $this->createPackage(MembershipService::TERM_YEARLY, [
                'id' => 24,
                'title' => 'الباقة المميزة سنوية',
            ]),
            25 => $this->createPackage(MembershipService::TERM_MONTHLY, [
                'id' => 25,
                'title' => 'الباقة المميزة الشهرية',
            ]),
            26 => $this->createPackage(MembershipService::TERM_TRIAL, [
                'id' => 26,
                'title' => 'الباقة التجريبية',
                'trial_days' => 7,
                'is_trial' => '1',
            ]),
            28 => $this->createPackage(MembershipService::TERM_MONTHLY, [
                'id' => 28,
                'title' => 'الباقة الشهرية للتجربة',
                'trial_days' => 30,
                'is_trial' => '1',
            ]),
            16 => $this->createPackage(MembershipService::TERM_YEARLY, [
                'id' => 16,
                'title' => 'الباقة المجانية',
                'is_active' => false,
            ]),
        ];
    }

    protected function createTenantUser(string $username): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'username' => $username,
            'email' => $username . '@example.test',
        ]);
    }

    protected function createPackage(string $term, array $attributes = []): Package
    {
        $payload = array_merge([
            'title' => Str::title($term) . ' Package',
            'slug' => Str::slug($term . '-' . ($attributes['id'] ?? Str::random(8))),
            'price' => 100,
            'term' => $term,
            'status' => '1',
            'is_active' => true,
        ], $attributes);

        if (array_key_exists('id', $attributes)) {
            return Package::unguarded(function () use ($payload) {
                return Package::query()->updateOrCreate(
                    ['id' => $payload['id']],
                    $payload
                );
            });
        }

        return Package::query()->create($payload);
    }

    protected function createCurrentMembership(User $user, Package $package, array $overrides = []): Membership
    {
        return Membership::query()->create(array_merge([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 1,
            'start_date' => now()->subDay()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
            'price' => 100,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'stripe',
            'transaction_id' => (string) Str::uuid(),
        ], $overrides));
    }

    private function createExpiredMembership(User $user, Package $package, array $overrides = []): Membership
    {
        return Membership::query()->create(array_merge([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 1,
            'start_date' => now()->subMonths(2)->toDateString(),
            'expire_date' => now()->subMonth()->toDateString(),
            'price' => 100,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'stripe',
            'transaction_id' => (string) Str::uuid(),
        ], $overrides));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RegisterUser;

use App\Domain\Admin\Models\Admin;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class RegisterUserListingTest extends AdminApiTestCase
{
    protected bool $shouldResetAdminData = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAdminViewData();
    }

    /** @test */
    public function paid_and_active_membership_filters_return_only_current_paid_subscribers(): void
    {
        $this->signInWebAdmin();

        $monthlyPackage = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => MembershipService::PAID_MONTHLY_PACKAGE_ID,
        ]);
        $yearlyPackage = $this->createPackage(MembershipService::TERM_YEARLY, [
            'id' => MembershipService::PAID_YEARLY_PACKAGE_ID,
        ]);
        $freePackage = $this->createPackage(MembershipService::TERM_YEARLY, [
            'id' => MembershipService::FREE_PACKAGE_ID,
        ]);

        $paidMonthlyUser = $this->createTenantUser('paid-monthly');
        $paidYearlyUser = $this->createTenantUser('paid-yearly');
        $trialUser = $this->createTenantUser('trial-user');
        $freeUser = $this->createTenantUser('free-user');
        $expiredPaidUser = $this->createTenantUser('expired-paid');
        $historicalPaidUser = $this->createTenantUser('historical-paid');

        $this->createCurrentMembership($paidMonthlyUser, $monthlyPackage);
        $this->createCurrentMembership($paidYearlyUser, $yearlyPackage);
        $this->createCurrentMembership($trialUser, $freePackage, [
            'package_id' => MembershipService::FREE_PACKAGE_ID,
            'transaction_details' => 'Trial',
            'payment_method' => '-',
        ]);
        $this->createCurrentMembership($freeUser, $freePackage, [
            'package_id' => MembershipService::FREE_PACKAGE_ID,
        ]);
        $this->createExpiredMembership($expiredPaidUser, $monthlyPackage);
        $this->createCurrentMembership($historicalPaidUser, $freePackage, [
            'package_id' => MembershipService::FREE_PACKAGE_ID,
        ]);
        $this->createExpiredMembership($historicalPaidUser, $monthlyPackage);

        $response = $this->get(route('admin.register.user', [
            'active_membership' => '1',
            'paid_member' => 'paid',
        ]));

        $response->assertOk();
        $this->assertUserListed($response, $paidMonthlyUser);
        $this->assertUserListed($response, $paidYearlyUser);
        $this->assertUserNotListed($response, $trialUser);
        $this->assertUserNotListed($response, $freeUser);
        $this->assertUserNotListed($response, $expiredPaidUser);
        $this->assertUserNotListed($response, $historicalPaidUser);
    }

    /** @test */
    public function subscription_expiry_filters_use_current_membership_only(): void
    {
        $this->signInWebAdmin();

        $monthlyPackage = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => MembershipService::PAID_MONTHLY_PACKAGE_ID,
        ]);
        $freePackage = $this->createPackage(MembershipService::TERM_YEARLY, [
            'id' => MembershipService::FREE_PACKAGE_ID,
        ]);

        $matchingUser = $this->createTenantUser('current-expiry-match');
        $staleHistoryUser = $this->createTenantUser('stale-history');

        $this->createCurrentMembership($matchingUser, $monthlyPackage, [
            'expire_date' => now()->addDays(10)->toDateString(),
        ]);
        $this->createCurrentMembership($staleHistoryUser, $freePackage, [
            'package_id' => MembershipService::FREE_PACKAGE_ID,
            'expire_date' => now()->addDays(5)->toDateString(),
        ]);
        $this->createExpiredMembership($staleHistoryUser, $monthlyPackage, [
            'expire_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->get(route('admin.register.user', [
            'subscription_start' => now()->addDays(8)->toDateString(),
            'subscription_end' => now()->addDays(12)->toDateString(),
        ]));

        $response->assertOk();
        $this->assertUserListed($response, $matchingUser);
        $this->assertUserNotListed($response, $staleHistoryUser);
    }

    /** @test */
    public function pagination_links_preserve_active_filters(): void
    {
        $this->signInWebAdmin();

        $monthlyPackage = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => MembershipService::PAID_MONTHLY_PACKAGE_ID,
        ]);

        foreach (range(1, 11) as $index) {
            $user = $this->createTenantUser('paid-user-' . $index);
            $this->createCurrentMembership($user, $monthlyPackage);
        }

        $response = $this->get(route('admin.register.user', [
            'active_membership' => '1',
            'paid_member' => 'paid',
            'term' => 'paid-user',
        ]));

        $response->assertOk();
        $response->assertSee('page=2', false);
        $response->assertSee('active_membership=1', false);
        $response->assertSee('paid_member=paid', false);
        $response->assertSee('term=paid-user', false);
    }

    /** @test */
    public function search_submissions_preserve_advanced_filters_without_page(): void
    {
        $this->signInWebAdmin();

        $monthlyPackage = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => MembershipService::PAID_MONTHLY_PACKAGE_ID,
        ]);
        $user = $this->createTenantUser('searchable-paid');
        $this->createCurrentMembership($user, $monthlyPackage);

        $response = $this->get(route('admin.register.user', [
            'term' => 'searchable-paid',
            'active_membership' => '1',
            'paid_member' => 'paid',
        ]));

        $response->assertOk();
        $response->assertDontSee('page=2', false);
        $this->assertUserListed($response, $user);
    }

    /** @test */
    public function advanced_filter_submissions_preserve_search_term_without_page(): void
    {
        $this->signInWebAdmin();

        $monthlyPackage = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => MembershipService::PAID_MONTHLY_PACKAGE_ID,
        ]);
        $user = $this->createTenantUser('filter-term-user');
        $this->createCurrentMembership($user, $monthlyPackage);

        $response = $this->get(route('admin.register.user', [
            'term' => 'filter-term-user',
            'active_membership' => '1',
            'paid_member' => 'paid',
        ]));

        $response->assertOk();
        $response->assertDontSee('page=2', false);
        $this->assertUserListed($response, $user);
    }

    /** @test */
    public function out_of_range_filtered_page_redirects_to_first_page_with_filters_intact(): void
    {
        $this->signInWebAdmin();

        $monthlyPackage = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => MembershipService::PAID_MONTHLY_PACKAGE_ID,
        ]);

        foreach (range(1, 5) as $index) {
            $user = $this->createTenantUser('paged-user-' . $index);
            $this->createCurrentMembership($user, $monthlyPackage);
        }

        $response = $this->get(route('admin.register.user', [
            'active_membership' => '1',
            'paid_member' => 'paid',
            'page' => 3,
        ]));

        $response->assertRedirect(route('admin.register.user', [
            'start_date' => '',
            'end_date' => '',
            'subscription_start' => '',
            'subscription_end' => '',
            'active_membership' => '1',
            'paid_member' => 'paid',
            'referred_by' => '',
        ]));
    }

    /** @test */
    public function empty_filtered_results_render_no_results_state(): void
    {
        $this->signInWebAdmin();

        $response = $this->get(route('admin.register.user', [
            'active_membership' => '1',
            'paid_member' => 'paid',
            'term' => 'no-such-tenant',
        ]));

        $response->assertOk();
        $response->assertSee(__('NO USER FOUND'), false);
    }

    /** @test */
    public function package_id_filter_returns_only_tenants_with_that_current_package(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        $yearlyUser = $this->createTenantUser('yearly-pkg');
        $monthlyUser = $this->createTenantUser('monthly-pkg');
        $trialUser = $this->createTenantUser('trial-pkg');
        $freeUser = $this->createTenantUser('free-pkg');

        $this->createCurrentMembership($yearlyUser, $packages[24]);
        $this->createCurrentMembership($monthlyUser, $packages[25]);
        $this->createCurrentMembership($trialUser, $packages[26]);
        $this->createCurrentMembership($freeUser, $packages[16]);

        $usersByPackageId = [
            24 => $yearlyUser,
            25 => $monthlyUser,
            26 => $trialUser,
            16 => $freeUser,
        ];

        foreach ($usersByPackageId as $packageId => $expectedUser) {
            $response = $this->get(route('admin.register.user', [
                'package_id' => $packageId,
            ]));

            $response->assertOk();
            $this->assertUserListed($response, $expectedUser);

            foreach ($usersByPackageId as $otherPackageId => $otherUser) {
                if ($otherPackageId === $packageId) {
                    continue;
                }

                $this->assertUserNotListed($response, $otherUser);
            }
        }
    }

    /** @test */
    public function package_id_filter_excludes_expired_and_historical_memberships(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        $currentYearlyUser = $this->createTenantUser('current-yearly');
        $expiredYearlyUser = $this->createTenantUser('expired-yearly');
        $historicalYearlyUser = $this->createTenantUser('historical-yearly');

        $this->createCurrentMembership($currentYearlyUser, $packages[24]);
        $this->createExpiredMembership($expiredYearlyUser, $packages[24]);
        $this->createCurrentMembership($historicalYearlyUser, $packages[16]);
        $this->createExpiredMembership($historicalYearlyUser, $packages[24]);

        $response = $this->get(route('admin.register.user', [
            'package_id' => 24,
        ]));

        $response->assertOk();
        $this->assertUserListed($response, $currentYearlyUser);
        $this->assertUserNotListed($response, $expiredYearlyUser);
        $this->assertUserNotListed($response, $historicalYearlyUser);
    }

    /** @test */
    public function package_id_26_does_not_include_legacy_free_package_trial(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        $trialPackageUser = $this->createTenantUser('trial-package-user');
        $legacyTrialUser = $this->createTenantUser('legacy-trial-user');

        $this->createCurrentMembership($trialPackageUser, $packages[26]);
        $this->createCurrentMembership($legacyTrialUser, $packages[16], [
            'package_id' => 16,
            'transaction_details' => 'Trial',
            'payment_method' => '-',
        ]);

        $response = $this->get(route('admin.register.user', [
            'package_id' => 26,
        ]));

        $response->assertOk();
        $this->assertUserListed($response, $trialPackageUser);
        $this->assertUserNotListed($response, $legacyTrialUser);
    }

    /** @test */
    public function pagination_links_preserve_package_id(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        foreach (range(1, 11) as $index) {
            $user = $this->createTenantUser('monthly-page-' . $index);
            $this->createCurrentMembership($user, $packages[25]);
        }

        $response = $this->get(route('admin.register.user', [
            'package_id' => 25,
            'term' => 'monthly-page-',
        ]));

        $response->assertOk();
        $response->assertSee('page=2', false);
        $response->assertSee('package_id=25', false);
    }

    /** @test */
    public function empty_package_id_results_still_render_filter_buttons(): void
    {
        $this->signInWebAdmin();

        $this->createPackageFilterPackages();

        $response = $this->get(route('admin.register.user', [
            'package_id' => 25,
            'term' => 'no-such-package-filter-tenant',
        ]));

        $response->assertOk();
        $response->assertSee(__('NO USER FOUND'), false);
        $response->assertSee('الباقة المميزة سنوية', false);
        $response->assertSee('الباقة المميزة الشهرية', false);
        $response->assertSee('الباقة التجريبية (7 أيام)', false);
        $response->assertSee('الباقة المجانية', false);
        $response->assertSee(__('Show All'), false);
    }

    /** @test */
    public function show_all_button_clears_package_filter_and_is_active_without_package_id(): void
    {
        $this->signInWebAdmin();

        $this->createPackageFilterPackages();

        $withoutFilter = $this->get(route('admin.register.user'));
        $withoutFilter->assertOk();
        $this->assertMatchesRegularExpression(
            '/href="' . preg_quote(route('admin.register.user'), '/') . '"\s+class="btn btn-primary">\s*'
            . preg_quote(__('Show All'), '/') . '/s',
            $withoutFilter->getContent()
        );

        $withFilter = $this->get(route('admin.register.user', [
            'package_id' => 25,
            'term' => 'pkg-filter-term',
        ]));
        $withFilter->assertOk();
        $this->assertMatchesRegularExpression(
            '/href="' . preg_quote(route('admin.register.user', ['term' => 'pkg-filter-term']), '/') . '"\s+class="btn btn-outline-primary">\s*'
            . preg_quote(__('Show All'), '/') . '/s',
            $withFilter->getContent()
        );
    }

    /** @test */
    public function out_of_range_package_id_page_redirects_and_keeps_package_id(): void
    {
        $this->signInWebAdmin();

        $packages = $this->createPackageFilterPackages();

        foreach (range(1, 5) as $index) {
            $user = $this->createTenantUser('pkg-paged-user-' . $index);
            $this->createCurrentMembership($user, $packages[25]);
        }

        $response = $this->get(route('admin.register.user', [
            'package_id' => 25,
            'term' => 'pkg-paged-user-',
            'page' => 3,
        ]));

        $response->assertRedirect(route('admin.register.user', [
            'term' => 'pkg-paged-user-',
            'package_id' => '25',
        ]));
    }

    /** @test */
    public function search_and_advanced_forms_preserve_package_id_when_present(): void
    {
        $this->signInWebAdmin();

        $this->createPackageFilterPackages();

        $withPackageId = $this->get(route('admin.register.user', [
            'package_id' => 25,
        ]));

        $withPackageId->assertOk();
        $this->assertSame(
            2,
            substr_count($withPackageId->getContent(), '<input type="hidden" name="package_id" value="25">')
        );

        $withoutPackageId = $this->get(route('admin.register.user'));

        $withoutPackageId->assertOk();
        $this->assertSame(
            0,
            substr_count($withoutPackageId->getContent(), '<input type="hidden" name="package_id"')
        );
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
                'is_trial' => 1,
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

    private function assertUserListed($response, User $user): void
    {
        $response->assertSee('data-val="' . $user->id . '"', false);
    }

    private function assertUserNotListed($response, User $user): void
    {
        $response->assertDontSee('data-val="' . $user->id . '"', false);
    }
}

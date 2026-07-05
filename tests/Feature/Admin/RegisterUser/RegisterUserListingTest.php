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
    protected bool $shouldResetAdminData = false;

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
        $languageId = DB::table('languages')->insertGetId([
            'name' => 'English',
            'code' => 'en',
            'is_default' => 1,
            'rtl' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('basic_settings')->insert([
            'language_id' => $languageId,
            'website_title' => 'Taearif',
            'timezone' => 'UTC',
            'logo' => 'logo.png',
            'favicon' => 'favicon.png',
            'copyright_text' => 'Taearif',
        ]);

        DB::table('basic_extendeds')->insert([
            'language_id' => $languageId,
        ]);

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

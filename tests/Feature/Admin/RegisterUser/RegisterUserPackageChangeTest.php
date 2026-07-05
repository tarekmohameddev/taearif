<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RegisterUser;

use App\Domain\Admin\Models\Admin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class RegisterUserPackageChangeTest extends AdminApiTestCase
{
    protected bool $shouldResetAdminData = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->ensureAdminViewData();
    }

    /** @test */
    public function admin_can_change_current_package(): void
    {
        $this->signInWebAdmin();

        $currentPackage = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => 901,
            'title' => 'Monthly Starter',
        ]);
        $newPackage = $this->createPackage(MembershipService::TERM_YEARLY, [
            'id' => 902,
            'title' => 'Yearly Pro',
        ]);

        $user = $this->createTenantUser('change-current');
        $currentMembership = $this->createCurrentMembership($user, $currentPackage);

        $response = $this->from(route('admin.register.user.view', $user->id))
            ->post(route('admin.user.currPackage.change'), [
                'user_id' => $user->id,
                'package_id' => $newPackage->id,
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect(route('admin.register.user.view', $user->id));
        $response->assertSessionHas('success');

        $currentMembership->refresh();

        $this->assertSame(
            Carbon::now()->subDay()->toDateString(),
            Carbon::parse($currentMembership->expire_date)->toDateString()
        );
        $this->assertSame(1, (int) $currentMembership->modified);

        $this->assertDatabaseHas('memberships', [
            'user_id' => $user->id,
            'package_id' => $newPackage->id,
            'payment_method' => 'manual',
            'status' => 1,
            'start_date' => Carbon::now()->toDateString(),
            'expire_date' => Carbon::now()->addYear()->toDateString(),
        ]);
    }

    /** @test */
    public function admin_can_add_current_package_when_user_has_none(): void
    {
        $this->signInWebAdmin();

        $package = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => 903,
            'title' => 'Assigned Monthly',
        ]);
        $user = $this->createTenantUser('add-current');

        $response = $this->from(route('admin.register.user.view', $user->id))
            ->post(route('admin.user.currPackage.add'), [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect(route('admin.register.user.view', $user->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('memberships', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_method' => 'manual',
            'status' => 1,
            'start_date' => Carbon::now()->toDateString(),
            'expire_date' => Carbon::now()->addMonth()->toDateString(),
        ]);
    }

    /** @test */
    public function admin_can_remove_current_package(): void
    {
        $this->signInWebAdmin();

        $package = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => 904,
            'title' => 'Removable Monthly',
        ]);
        $user = $this->createTenantUser('remove-current');
        $membership = $this->createCurrentMembership($user, $package);

        $response = $this->from(route('admin.register.user.view', $user->id))
            ->post(route('admin.user.currPackage.remove'), [
                'user_id' => $user->id,
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect(route('admin.register.user.view', $user->id));
        $response->assertSessionHas('success');

        $membership->refresh();

        $this->assertSame(
            Carbon::now()->subDay()->toDateString(),
            Carbon::parse($membership->expire_date)->toDateString()
        );
        $this->assertSame(1, (int) $membership->modified);
    }

    /** @test */
    public function change_to_lifetime_is_blocked_when_next_package_exists(): void
    {
        $this->signInWebAdmin();

        $currentPackage = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => 905,
            'title' => 'Current Monthly',
        ]);
        $nextPackage = $this->createPackage(MembershipService::TERM_YEARLY, [
            'id' => 906,
            'title' => 'Queued Yearly',
        ]);
        $lifetimePackage = $this->createPackage(MembershipService::TERM_LIFETIME, [
            'id' => 907,
            'title' => 'Lifetime',
        ]);

        $user = $this->createTenantUser('lifetime-blocked');
        $currentMembership = $this->createCurrentMembership($user, $currentPackage, [
            'expire_date' => now()->addMonth()->toDateString(),
        ]);
        $this->createNextMembership($user, $nextPackage, $currentMembership);

        $response = $this->from(route('admin.register.user.view', $user->id))
            ->post(route('admin.user.currPackage.change'), [
                'user_id' => $user->id,
                'package_id' => $lifetimePackage->id,
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect(route('admin.register.user.view', $user->id));
        $response->assertSessionHas('membership_warning');

        $this->assertDatabaseMissing('memberships', [
            'user_id' => $user->id,
            'package_id' => $lifetimePackage->id,
        ]);
    }

    /** @test */
    public function admin_can_change_next_package(): void
    {
        $this->signInWebAdmin();

        $currentPackage = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => 908,
            'title' => 'Active Monthly',
        ]);
        $queuedPackage = $this->createPackage(MembershipService::TERM_YEARLY, [
            'id' => 909,
            'title' => 'Queued Yearly',
        ]);
        $replacementPackage = $this->createPackage(MembershipService::TERM_YEARLY, [
            'id' => 910,
            'title' => 'Replacement Yearly',
        ]);

        $user = $this->createTenantUser('change-next');
        $currentMembership = $this->createCurrentMembership($user, $currentPackage, [
            'expire_date' => now()->addMonth()->toDateString(),
        ]);
        $nextMembership = $this->createNextMembership($user, $queuedPackage, $currentMembership);
        $nextStartDate = Carbon::parse($nextMembership->start_date)->toDateString();

        $response = $this->from(route('admin.register.user.view', $user->id))
            ->post(route('admin.user.nextPackage.change'), [
                'user_id' => $user->id,
                'package_id' => $replacementPackage->id,
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect(route('admin.register.user.view', $user->id));
        $response->assertSessionHas('success');

        $nextMembership->refresh();

        $this->assertSame(
            Carbon::maxValue()->format('Y-m-d'),
            Carbon::parse($nextMembership->start_date)->toDateString()
        );
        $this->assertSame(1, (int) $nextMembership->modified);

        $this->assertDatabaseHas('memberships', [
            'user_id' => $user->id,
            'package_id' => $replacementPackage->id,
            'payment_method' => 'manual',
            'status' => 1,
            'start_date' => $nextStartDate,
            'expire_date' => Carbon::parse($nextStartDate)->addYear()->toDateString(),
        ]);
    }

    /** @test */
    public function guest_cannot_change_current_package(): void
    {
        $user = $this->createTenantUser('guest-change');
        $package = $this->createPackage(MembershipService::TERM_MONTHLY, ['id' => 911]);
        $this->createCurrentMembership($user, $package);

        $response = $this->post(route('admin.user.currPackage.change'), [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_method' => 'manual',
        ]);

        $response->assertRedirect();
    }

    private function signInWebAdmin(): Admin
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

    private function ensureAdminViewData(): void
    {
        if (! DB::table('languages')->exists()) {
            $languageId = DB::table('languages')->insertGetId([
                'name' => 'English',
                'code' => 'en',
                'is_default' => 1,
                'rtl' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $languageId = DB::table('languages')->where('is_default', 1)->value('id')
                ?? DB::table('languages')->value('id');
        }

        if (! DB::table('basic_settings')->exists()) {
            DB::table('basic_settings')->insert([
                'language_id' => $languageId,
                'website_title' => 'Taearif',
                'timezone' => 'UTC',
                'logo' => 'logo.png',
                'favicon' => 'favicon.png',
                'copyright_text' => 'Taearif',
            ]);
        }

        if (! DB::table('basic_extendeds')->exists()) {
            DB::table('basic_extendeds')->insert([
                'language_id' => $languageId,
                'base_currency_text' => 'USD',
                'base_currency_symbol' => '$',
            ]);
        }

        $currentLang = \App\Models\Language::query()
            ->with(['basic_setting', 'basic_extended'])
            ->where('is_default', 1)
            ->first();

        if ($currentLang) {
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
    }

    private function createTenantUser(string $username): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'username' => $username,
            'email' => $username . '@example.test',
        ]);
    }

    private function createPackage(string $term, array $attributes = []): Package
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

    private function createCurrentMembership(User $user, Package $package, array $overrides = []): Membership
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

    private function createNextMembership(User $user, Package $package, Membership $currentMembership): Membership
    {
        $startDate = Carbon::parse($currentMembership->expire_date)->addDay()->toDateString();
        $expireDate = $package->term === MembershipService::TERM_YEARLY
            ? Carbon::parse($startDate)->addYear()->toDateString()
            : Carbon::parse($startDate)->addMonth()->toDateString();

        return Membership::query()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 1,
            'start_date' => $startDate,
            'expire_date' => $expireDate,
            'price' => 100,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'manual',
            'transaction_id' => (string) Str::uuid(),
        ]);
    }
}

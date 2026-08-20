<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RegisterUser;

use App\Domain\Admin\Models\Admin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Api\GeneralSetting;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class RegisterUserMaintenanceToggleTest extends AdminApiTestCase
{
    protected bool $shouldResetAdminData = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->ensureAdminViewData();
    }

    /** @test */
    public function admin_post_toggles_maintenance_mode_on_then_off(): void
    {
        $this->signInWebAdmin();
        $user = $this->createTenantUser('maint-toggle');

        $this->from(route('admin.register.user'))
            ->post(route('admin.register.user.maintenance'), ['user_id' => $user->id])
            ->assertRedirect()
            ->assertSessionHas('success', __('Site set under maintenance'));

        $this->assertDatabaseHas('api_general_settings', [
            'user_id' => $user->id,
            'maintenance_mode' => 1,
        ]);

        $this->from(route('admin.register.user'))
            ->post(route('admin.register.user.maintenance'), ['user_id' => $user->id])
            ->assertRedirect()
            ->assertSessionHas('success', __('Site is back online'));

        $this->assertDatabaseHas('api_general_settings', [
            'user_id' => $user->id,
            'maintenance_mode' => 0,
        ]);
    }

    /** @test */
    public function admin_can_toggle_maintenance_mode_for_a_free_package_user(): void
    {
        $this->signInWebAdmin();

        $freePackage = $this->createPackage(MembershipService::TERM_YEARLY, [
            'id' => MembershipService::FREE_PACKAGE_ID,
        ]);
        $user = $this->createTenantUser('maint-free');
        $this->createCurrentMembership($user, $freePackage, [
            'package_id' => MembershipService::FREE_PACKAGE_ID,
        ]);

        $this->from(route('admin.register.user'))
            ->post(route('admin.register.user.maintenance'), ['user_id' => $user->id])
            ->assertRedirect()
            ->assertSessionHas('success', __('Site set under maintenance'));

        $this->assertDatabaseHas('api_general_settings', [
            'user_id' => $user->id,
            'maintenance_mode' => 1,
        ]);

        $this->from(route('admin.register.user'))
            ->post(route('admin.register.user.maintenance'), ['user_id' => $user->id])
            ->assertRedirect()
            ->assertSessionHas('success', __('Site is back online'));

        $this->assertDatabaseHas('api_general_settings', [
            'user_id' => $user->id,
            'maintenance_mode' => 0,
        ]);
    }

    /** @test */
    public function listing_shows_under_maintenance_badge_and_toggle_labels(): void
    {
        $this->signInWebAdmin();
        $user = $this->createTenantUser('maint-listed');
        $this->setMaintenanceFlag($user, 1);

        $onResponse = $this->get(route('admin.register.user', [
            'term' => $user->username,
        ]));

        $onResponse->assertOk();
        $onRow = $this->userRowHtml($onResponse->getContent(), $user);
        $this->assertStringContainsString('https://' . $user->username . '.taearif.com/ar/', $onRow);
        $this->assertStringContainsString(__('Under Maintenance'), $onRow);
        $this->assertStringContainsString(__('Disable Maintenance Mode'), $onRow);

        $this->setMaintenanceFlag($user, 0);

        $offResponse = $this->get(route('admin.register.user', [
            'term' => $user->username,
        ]));

        $offResponse->assertOk();
        $offRow = $this->userRowHtml($offResponse->getContent(), $user);
        $this->assertStringContainsString('https://' . $user->username . '.taearif.com/ar/', $offRow);
        $this->assertStringContainsString(__('Enable Maintenance Mode'), $offRow);
        $this->assertStringNotContainsString(__('Disable Maintenance Mode'), $offRow);
        $this->assertStringNotContainsString('<span class="badge badge-warning">' . __('Under Maintenance') . '</span>', $offRow);
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

    protected function createTenantUser(string $username): User
    {
        $unique = $username . '-' . Str::lower(Str::random(8));

        $user = User::factory()->create([
            'account_type' => 'tenant',
            'username' => $unique,
            'email' => $unique . '@example.test',
        ]);

        GeneralSetting::where('user_id', $user->id)->delete();

        return $user;
    }

    private function setMaintenanceFlag(User $user, int $enabled): void
    {
        GeneralSetting::where('user_id', $user->id)->delete();
        GeneralSetting::create([
            'user_id' => $user->id,
            'maintenance_mode' => $enabled,
        ]);
    }

    private function userRowHtml(string $html, User $user): string
    {
        $pattern = '/<tr>\s*<td>\s*<input type="checkbox" class="bulk-check" data-val="'
            . preg_quote((string) $user->id, '/')
            . '"[\s\S]*?<\/tr>/';

        $this->assertSame(1, preg_match($pattern, $html, $matches), 'Expected user row in listing HTML.');

        return $matches[0];
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
}

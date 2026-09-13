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

class RegisterUserDetailsTest extends AdminApiTestCase
{
    protected bool $shouldResetAdminData = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAdminViewData();
    }

    /** @test */
    public function details_page_renders_user_info(): void
    {
        $this->signInWebAdmin();

        $package = $this->createPackage(MembershipService::TERM_MONTHLY, [
            'id' => MembershipService::PAID_MONTHLY_PACKAGE_ID,
        ]);
        $user = $this->createTenantUser('details-page-user');
        $this->createCurrentMembership($user, $package);

        $response = $this->get(route('admin.register.user.view', $user->id));

        $response->assertOk();
        $response->assertSee(__('Customer Details'), false);
        $response->assertSee($user->username, false);
        $response->assertSee($user->email, false);
        $this->assertNotSame('', trim(strip_tags($response->getContent())));
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
        return User::factory()->create([
            'account_type' => 'tenant',
            'username' => $username,
            'email' => $username . '@example.test',
            'first_name' => 'Details',
            'last_name' => 'User',
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
}

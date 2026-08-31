<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Feature\Admin\AdminApiTestCase;

class CustomDomainCapacityTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAdminViewData();
        Cache::forget('vercel.project_domain_count');
    }

    protected function tearDown(): void
    {
        Cache::forget('vercel.project_domain_count');

        parent::tearDown();
    }

    /** @test */
    public function page_renders_capacity_when_vercel_api_responds(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => $this->fakeDomainList(46),
                'pagination' => ['count' => 46, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        // Anchor on values and structure, not copy: the admin layout renders in
        // Arabic, so asserting labels couples this test to the translation files.
        $response->assertSee('data-lucide="gauge"', false);
        $response->assertSee('46 / 50', false);
        $response->assertSee('>21<', false);   // customer domains in use
        $response->assertSee('>2</h4>', false); // can still add
        $response->assertSee('92%', false);
        $response->assertSee($domain->custom_name, false);
    }

    /** @test */
    public function page_still_renders_domains_list_when_api_fails(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response(['error' => ['message' => 'upstream error']], 500),
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertDontSee('data-lucide="gauge"', false);
        $response->assertSee($domain->custom_name, false);
    }

    /** @test */
    public function page_still_renders_when_vercel_is_not_configured(): void
    {
        $this->skipIfMissingSchema();
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();

        config([
            'services.vercel.token' => null,
            'services.vercel.project_id' => null,
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertDontSee('data-lucide="gauge"', false);
        $response->assertSee($domain->custom_name, false);
    }

    /** @test */
    public function pagination_is_followed_and_totals_are_summed(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $this->seedDomainSetting();

        $page1Until = 1788086333258;

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($page1Until) {
            $url = $request->url();

            if (! str_contains($url, '/v9/projects/') || ! str_contains($url, '/domains')) {
                return Http::response(['error' => 'unexpected'], 500);
            }

            if (str_contains($url, 'until=')) {
                return Http::response([
                    'domains' => $this->fakeDomainList(20),
                    'pagination' => ['count' => 20, 'next' => null, 'prev' => $page1Until],
                ], 200);
            }

            return Http::response([
                'domains' => $this->fakeDomainList(80),
                'pagination' => ['count' => 80, 'next' => $page1Until, 'prev' => null],
            ], 200);
        });

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee('100 / 50', false);
    }

    /** @test */
    public function remaining_customer_domains_accounts_for_two_entries_per_domain(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => $this->fakeDomainList(48),
                'pagination' => ['count' => 48, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee('48 / 50', false);
        // (48 - 4 platform) / 2 = 22 in use; (50 - 48) / 2 = 1 remaining.
        $response->assertSee('>22<', false);    // (48 - 4 platform) / 2
        $response->assertSee('>1</h4>', false); // (50 - 48) / 2
        $response->assertSee('96%', false);
    }

    private function skipIfMissingSchema(): void
    {
        if (! Schema::hasTable('api_domains_settings')) {
            $this->markTestSkipped('Missing required DB tables.');
        }
    }

    private function configureVercel(): void
    {
        config([
            'services.vercel.token' => 'test-token',
            'services.vercel.project_id' => 'prj_test',
            'services.vercel.team_id' => 'team_test',
            'services.vercel.base_url' => 'https://api.vercel.com',
            'services.vercel.max_project_domains' => 50,
            'services.vercel.platform_domain_count' => 4,
        ]);
    }

    private function signInWebAdmin(): Admin
    {
        $admin = Admin::factory()->create([
            'status' => true,
            'role_id' => null,
        ]);

        $this->app['auth']->guard('admin')->setUser($admin);

        View::share([
            'adminUser' => $admin,
        ]);

        return $admin;
    }

    private function seedDomainSetting(): ApiDomainSetting
    {
        $user = User::factory()->tenant()->create([
            'email' => 'capacity-domain-' . uniqid('', true) . '@example.com',
        ]);

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => 'capacity-' . uniqid('', false) . '.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);
    }

    /**
     * @return list<array{name: string}>
     */
    private function fakeDomainList(int $count): array
    {
        $domains = [];

        for ($i = 0; $i < $count; $i++) {
            $domains[] = ['name' => 'domain-' . $i . '.example.com'];
        }

        return $domains;
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

        $settingsPayload = [
            'language_id' => $languageId,
            'website_title' => 'Taearif',
            'timezone' => 'UTC',
            'logo' => 'logo.png',
            'favicon' => 'favicon.png',
        ];

        if (Schema::hasColumn('basic_settings', 'copyright_text')) {
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
}

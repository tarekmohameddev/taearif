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

class CustomDomainHealthFilterTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAdminViewData();
        Cache::forget('admin.domain_health_counts');
        Cache::forget('vercel.project_domain_count');
        Cache::forget('vercel.project_domain_names');
    }

    protected function tearDown(): void
    {
        Cache::forget('admin.domain_health_counts');
        Cache::forget('vercel.project_domain_count');
        Cache::forget('vercel.project_domain_names');

        parent::tearDown();
    }

    /** @test */
    public function health_issues_filter_excludes_unchecked_and_linked(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $linked = $this->seedDomainWithHealth([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => true,
            'nameservers_ok' => true,
        ]);

        $issue = $this->seedDomainWithHealth([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => true,
            'nameservers_ok' => false,
            'message' => 'Nameservers are not pointing to Vercel yet.',
        ]);

        $unchecked = $this->seedDomainWithHealth([]);

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => $linked->custom_name],
                    ['name' => $issue->custom_name],
                ],
                'pagination' => ['count' => 2, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'issues']));

        $response->assertOk();
        $response->assertSee($issue->custom_name, false);
        $response->assertDontSee($unchecked->custom_name, false);
        $response->assertDontSee($linked->custom_name, false);
        $response->assertSee(__('domain_health.filter_confirmed_issues'), false);
    }

    /** @test */
    public function health_unchecked_filter_shows_only_never_checked_domains(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $unchecked = $this->seedDomainWithHealth([]);

        $issue = $this->seedDomainWithHealth([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => true,
            'nameservers_ok' => false,
            'message' => 'Nameservers are not pointing to Vercel yet.',
        ]);

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => $issue->custom_name],
                ],
                'pagination' => ['count' => 1, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'unchecked']));

        $response->assertOk();
        $response->assertSee($unchecked->custom_name, false);
        $response->assertDontSee($issue->custom_name, false);
        $response->assertSee(__('domain_health.filter_unchecked'), false);
    }

    /** @test */
    public function health_linked_filter_shows_only_working_domains(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $linked = $this->seedDomainWithHealth([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => true,
            'nameservers_ok' => true,
        ]);

        $issue = $this->seedDomainWithHealth([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => true,
            'nameservers_ok' => false,
            'message' => 'Nameservers are not pointing to Vercel yet.',
        ]);

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => $linked->custom_name],
                    ['name' => $issue->custom_name],
                ],
                'pagination' => ['count' => 2, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'linked']));

        $response->assertOk();
        $response->assertSee($linked->custom_name, false);
        $response->assertDontSee($issue->custom_name, false);
        $response->assertSee(__('domain_health.filter_linked'), false);
    }

    /** @test */
    public function health_not_on_vercel_filter_shows_only_orphan_candidates(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $orphan = $this->seedDomainWithHealth([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => false,
            'nameservers_ok' => false,
            'vercel_attached' => false,
        ]);

        $onVercel = $this->seedDomainWithHealth([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => false,
            'nameservers_ok' => false,
            'vercel_attached' => true,
        ]);

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => $onVercel->custom_name],
                ],
                'pagination' => ['count' => 1, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'not_on_vercel']));

        $response->assertOk();
        $response->assertSee($orphan->custom_name, false);
        $response->assertDontSee($onVercel->custom_name, false);
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

    /**
     * @param  array<string, mixed>  $lastCheck
     */
    private function seedDomainWithHealth(array $lastCheck): ApiDomainSetting
    {
        $user = User::factory()->tenant()->create([
            'email' => 'health-filter-' . uniqid('', true) . '@example.com',
        ]);

        $dnsRecords = $lastCheck === [] ? [] : ['last_check' => $lastCheck];

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => 'health-filter-' . uniqid('', false) . '.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => $dnsRecords,
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

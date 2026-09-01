<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DnsNameserverChecker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Feature\Admin\AdminApiTestCase;

class CustomDomainRecheckTest extends AdminApiTestCase
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
    public function recheck_clears_domain_health_counts_cache(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();

        Cache::put('admin.domain_health_counts', ['linked' => 9, 'issues' => 3], now()->addMinutes(5));
        $this->assertTrue(Cache::has('admin.domain_health_counts'));

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'name' => $domain->custom_name,
                'verified' => true,
            ], 200),
        ]);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.recheck'), [
                'domain_id' => $domain->id,
            ])
            ->assertRedirect();

        $this->assertFalse(Cache::has('admin.domain_health_counts'));
    }

    /** @test */
    public function recheck_runs_sync_and_persists_last_check(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domain) {
            $url = $request->url();
            $name = $domain->custom_name;

            if (str_contains($url, '/verify')) {
                return Http::response([
                    'name' => $name,
                    'verified' => true,
                ], 200);
            }

            if (str_contains($url, '/v9/projects/') && str_contains($url, '/domains/')) {
                return Http::response([
                    'name' => $name,
                    'verified' => true,
                ], 200);
            }

            return Http::response(['error' => ['message' => 'unexpected']], 500);
        });

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.recheck'), [
                'domain_id' => $domain->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $domain->refresh();
        $lastCheck = $domain->dns_records['last_check'] ?? null;

        $this->assertIsArray($lastCheck);
        $this->assertTrue($lastCheck['vercel_verified']);
        $this->assertTrue($lastCheck['nameservers_ok']);
        $this->assertNotEmpty($lastCheck['last_check_at']);
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
            'services.vercel.auto_attach_custom_domain' => true,
            'services.vercel.check_nameservers' => true,
        ]);
    }

    private function mockNameservers(bool $ok): void
    {
        $this->mock(DnsNameserverChecker::class, function ($mock) use ($ok) {
            $mock->shouldReceive('hasExpectedNameservers')->andReturn($ok);
        });
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

    private function seedDomainSetting(?string $customName = null): ApiDomainSetting
    {
        $user = User::factory()->tenant()->create([
            'email' => 'recheck-domain-' . uniqid('', true) . '@example.com',
        ]);

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $customName ?? ('recheck-' . uniqid('', false) . '.example.com'),
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
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

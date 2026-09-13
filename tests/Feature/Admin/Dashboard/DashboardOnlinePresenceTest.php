<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Dashboard;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\Role;
use App\Services\Admin\AdminDashboardMetricsService;
use App\Services\Analytics\DashboardPresenceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Mockery\MockInterface;
use Tests\Feature\Admin\AdminApiTestCase;

class DashboardOnlinePresenceTest extends AdminApiTestCase
{
    protected bool $shouldResetAdminData = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureAdminViewData();
    }

    /** @test */
    public function authorized_admin_can_fetch_online_presence_snapshot(): void
    {
        $admin = $this->adminWithPermissions(['Dashboard']);
        $this->actingAs($admin, 'admin');

        $payload = [
            'available' => true,
            'online_users' => 17,
            'online_tenant_organizations' => 9,
            'window_seconds' => 120,
            'as_of' => '2026-09-08T10:00:00Z',
            'definition' => 'distinct_authenticated_dashboard_accounts',
            'reason' => null,
        ];

        $this->mock(DashboardPresenceService::class, function (MockInterface $mock) use ($payload): void {
            $mock->shouldReceive('snapshot')->once()->andReturn($payload);
        });

        $this->getJson(route('admin.dashboard.online-presence'))
            ->assertOk()
            ->assertExactJson($payload);
    }

    /** @test */
    public function degraded_online_presence_returns_200_with_unavailable_payload(): void
    {
        $admin = $this->adminWithPermissions(['Dashboard']);
        $this->actingAs($admin, 'admin');

        $payload = [
            'available' => false,
            'online_users' => null,
            'online_tenant_organizations' => null,
            'window_seconds' => 120,
            'as_of' => '2026-09-08T10:00:00Z',
            'definition' => 'distinct_authenticated_dashboard_accounts',
            'reason' => 'redis_unavailable',
        ];

        $this->mock(DashboardPresenceService::class, function (MockInterface $mock) use ($payload): void {
            $mock->shouldReceive('snapshot')->once()->andReturn($payload);
        });

        $this->getJson(route('admin.dashboard.online-presence'))
            ->assertOk()
            ->assertExactJson($payload);
    }

    /** @test */
    public function dashboard_view_renders_live_presence_cards_and_polling_hooks(): void
    {
        $admin = $this->adminWithPermissions(['Dashboard', 'Registered Users']);
        $this->actingAs($admin, 'admin');

        $this->mockDashboardMetricsWithPresence([
            'available' => false,
            'online_users' => null,
            'online_tenant_organizations' => null,
            'window_seconds' => 120,
            'as_of' => '2026-09-08T10:00:00Z',
            'definition' => 'distinct_authenticated_dashboard_accounts',
            'reason' => 'redis_unavailable',
        ]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('Live Presence'), false);
        $response->assertSee(__('Dashboard Users Online Now'), false);
        $response->assertSee(__('Tenant Organizations Online Now'), false);
        $response->assertSee(__('Live dashboard presence is temporarily unavailable'), false);
        $response->assertSee('data-dashboard-live-presence', false);
        $response->assertSee('data-dashboard-live-key="online_users"', false);
        $response->assertSee('data-dashboard-live-key="online_tenant_organizations"', false);
        $this->assertMatchesRegularExpression(
            '/data-dashboard-live-key="online_users"[^>]*>[\s\S]*?<strong class="dashboard-kpi-value">—<\/strong>/u',
            $response->getContent()
        );
    }

    /** @test */
    public function healthy_zero_presence_renders_literal_zero_not_em_dash(): void
    {
        $admin = $this->adminWithPermissions(['Dashboard', 'Registered Users']);
        $this->actingAs($admin, 'admin');

        $this->mockDashboardMetricsWithPresence([
            'available' => true,
            'online_users' => 0,
            'online_tenant_organizations' => 0,
            'window_seconds' => 120,
            'as_of' => '2026-09-08T10:00:00Z',
            'definition' => 'distinct_authenticated_dashboard_accounts',
            'reason' => null,
        ]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('Dashboard Users Online Now'), false);
        $response->assertSee(__('Tenant Organizations Online Now'), false);
        $response->assertSee(
            __('Distinct eligible dashboard accounts active in the last :minutes minutes', ['minutes' => 2]),
            false
        );

        $html = $response->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/<p class="dashboard-kpi-helper">' . preg_quote(__('Live dashboard presence is temporarily unavailable'), '/') . '<\/p>/u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-dashboard-live-key="online_users"[^>]*>[\s\S]*?<strong class="dashboard-kpi-value">0<\/strong>/u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-dashboard-live-key="online_tenant_organizations"[^>]*>[\s\S]*?<strong class="dashboard-kpi-value">0<\/strong>/u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/data-dashboard-live-key="online_users"[^>]*>[\s\S]*?<strong class="dashboard-kpi-value">—<\/strong>/u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/data-dashboard-live-key="online_tenant_organizations"[^>]*>[\s\S]*?<strong class="dashboard-kpi-value">—<\/strong>/u',
            $html
        );
    }

    /** @test */
    public function guests_are_redirected_and_admins_without_dashboard_permission_are_blocked(): void
    {
        $this->get(route('admin.dashboard.online-presence'))
            ->assertRedirect(route('admin.login'));

        $admin = $this->adminWithPermissions(['Registered Users']);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.dashboard.online-presence'))
            ->assertRedirect(route('admin.dashboard'));
    }

    private function adminWithPermissions(array $permissions): Admin
    {
        $role = Role::factory()->create(['permissions' => $permissions]);

        return Admin::factory()->create([
            'role_id' => $role->id,
            'status' => true,
        ]);
    }

    private function mockDashboardMetricsWithPresence(array $presence): void
    {
        $this->mock(AdminDashboardMetricsService::class, function (MockInterface $mock) use ($presence): void {
            $mock->shouldReceive('build')->once()->andReturn([
                'asOf' => now(AdminDashboardMetricsService::BUSINESS_TIMEZONE),
                'visibility' => [
                    'users' => true,
                    'packages' => false,
                    'tenantOperations' => false,
                    'financial' => false,
                ],
                'presence' => $presence,
                'executiveSummary' => [],
                'operationsSnapshot' => [],
                'financialMetrics' => [],
                'trendCharts' => [],
                'breakdowns' => [],
                'recentActivity' => [],
            ]);
        });
    }

    private function ensureAdminViewData(): void
    {
        if (! Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->boolean('is_default')->default(false);
                $table->boolean('rtl')->default(false);
                $table->timestamps();
            });
        }

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

        if (! Schema::hasTable('basic_settings')) {
            Schema::create('basic_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('language_id')->nullable();
                $table->string('website_title')->nullable();
                $table->string('timezone')->nullable();
                $table->string('logo')->nullable();
                $table->string('favicon')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('basic_extendeds')) {
            Schema::create('basic_extendeds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('language_id')->nullable();
            });
        }

        $settings = [
            'language_id' => $languageId,
            'website_title' => 'Taearif',
            'timezone' => AdminDashboardMetricsService::BUSINESS_TIMEZONE,
            'logo' => 'logo.png',
            'favicon' => 'favicon.png',
        ];

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

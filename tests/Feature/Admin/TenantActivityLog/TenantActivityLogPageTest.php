<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\TenantActivityLog;

use App\Domain\Admin\Models\Admin;
use App\Models\TenantWebsiteSavePagesLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\Feature\Admin\AdminApiTestCase;

class TenantActivityLogPageTest extends AdminApiTestCase
{
    protected bool $shouldResetAdminData = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAdminViewData();
    }

    /** @test */
    public function index_lists_tenants_and_supports_search(): void
    {
        $this->signInWebAdmin();

        $matching = $this->createTenantUser('search-target');
        $other = $this->createTenantUser('unrelated-tenant');

        $response = $this->get(route('admin.tenant-activity-logs.index', ['term' => 'search-target']));

        $response->assertOk();
        $response->assertSee($matching->username);
        $response->assertDontSee($other->username);
    }

    /** @test */
    public function show_displays_save_pages_logs_for_the_given_tenant(): void
    {
        $this->signInWebAdmin();

        $tenant = $this->createTenantUser('logged-tenant');
        $otherTenant = $this->createTenantUser('other-tenant');

        TenantWebsiteSavePagesLog::create([
            'tenant_id' => $tenant->id,
            'username' => $tenant->username,
            'tenant_id_value' => $tenant->username,
            'login_session_meta' => [
                'loginSource' => 'User',
                'loginIp' => '10.0.0.1',
            ],
            'server_ip' => '10.0.0.1',
            'server_user_agent' => 'PHPUnit',
            'before' => ['websiteName' => $tenant->username, 'pages' => []],
            'after' => ['websiteName' => $tenant->username, 'pages' => ['homepage' => []]],
            'created_at' => now(),
        ]);

        TenantWebsiteSavePagesLog::create([
            'tenant_id' => $otherTenant->id,
            'username' => $otherTenant->username,
            'tenant_id_value' => $otherTenant->username,
            'login_session_meta' => [],
            'before' => [],
            'after' => [],
            'created_at' => now(),
        ]);

        $response = $this->get(route('admin.tenant-activity-logs.show', $tenant->id));

        $response->assertOk();
        $response->assertSee('User');
        $response->assertSee('10.0.0.1');
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
}

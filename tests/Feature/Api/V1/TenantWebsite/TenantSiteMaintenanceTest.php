<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Http\Middleware\SetTenantForPermissions;
use App\Models\Api\GeneralSetting;
use App\Models\TenantGlobalComponent;
use App\Models\TenantPage;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantSiteMaintenanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'api_general_settings'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        $this->ensureApiDomainsSettingsTable();
        $this->ensureTenantPagesTable();
        $this->withoutMiddleware(SetTenantForPermissions::class);
    }

    private function ensureApiDomainsSettingsTable(): void
    {
        if (Schema::hasTable('api_domains_settings')) {
            return;
        }

        Schema::create('api_domains_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('custom_name')->nullable();
            $table->string('status')->nullable();
            $table->boolean('primary')->default(false);
            $table->timestamps();
        });
    }

    private function ensureTenantPagesTable(): void
    {
        if (Schema::hasTable('tenant_pages')) {
            return;
        }

        Schema::create('tenant_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('page_id');
            $table->json('components')->nullable();
            $table->json('published_data')->nullable();
            $table->timestamps();
        });
    }

    public function test_public_pages_return_200_when_maintenance_flag_is_off(): void
    {
        $tenant = $this->createTenant('maint-off');

        $this->getJson($this->pagesUrl($tenant))
            ->assertOk();
    }

    public function test_public_pages_return_200_when_general_setting_row_is_missing(): void
    {
        $tenant = $this->createTenant('maint-missing');

        $this->assertDatabaseMissing('api_general_settings', [
            'user_id' => $tenant->id,
        ]);

        $this->getJson($this->pagesUrl($tenant))
            ->assertOk();
    }

    public function test_anonymous_request_returns_503_when_maintenance_flag_is_on(): void
    {
        $tenant = $this->createTenant('maint-anon');
        $this->enableMaintenance($tenant);

        $this->getJson($this->pagesUrl($tenant))
            ->assertStatus(503)
            ->assertJsonPath('maintenance', true);
    }

    public function test_owner_sanctum_token_bypasses_maintenance_gate(): void
    {
        $tenant = $this->createTenant('maint-owner');
        $this->enableMaintenance($tenant);

        $this->actingAs($tenant, 'sanctum')
            ->getJson($this->pagesUrl($tenant))
            ->assertOk();
    }

    public function test_other_tenant_sanctum_token_is_blocked_when_flag_is_on(): void
    {
        $tenant = $this->createTenant('maint-target');
        $other = $this->createTenant('maint-other');
        $this->enableMaintenance($tenant);

        $this->actingAs($other, 'sanctum')
            ->getJson($this->pagesUrl($tenant))
            ->assertStatus(503)
            ->assertJsonPath('maintenance', true);
    }

    public function test_get_tenant_fails_open_and_includes_maintenance_mode(): void
    {
        $maintained = $this->createTenant('maint-get-a');
        $online = $this->createTenant('maint-get-b');
        $this->enableMaintenance($maintained);
        $this->seedWebsiteData($maintained);
        $this->seedWebsiteData($online);

        $this->getJson($this->pagesUrl($maintained))
            ->assertStatus(503)
            ->assertJsonPath('maintenance', true);

        $this->postJson('/api/v1/tenant-website/getTenant', [
            'websiteName' => $online->username,
        ])
            ->assertOk()
            ->assertJsonPath('maintenance_mode', false);

        $this->postJson('/api/v1/tenant-website/getTenant', [
            'websiteName' => $maintained->username,
        ])
            ->assertOk()
            ->assertJsonPath('maintenance_mode', true);
    }

    private function pagesUrl(User $tenant): string
    {
        return "/api/v1/tenant-website/{$tenant->username}/pages";
    }

    private function createTenant(string $prefix): User
    {
        $username = $prefix . '-' . Str::lower(Str::random(8));

        $attributes = [
            'account_type' => 'tenant',
            'username' => $username,
            'email' => $username . '@example.test',
        ];

        if (Schema::hasColumn('users', 'rbac_version')) {
            $attributes['rbac_version'] = 99;
        }
        if (Schema::hasColumn('users', 'rbac_seeded_at')) {
            $attributes['rbac_seeded_at'] = now();
        }

        $user = User::factory()->create($attributes);

        GeneralSetting::where('user_id', $user->id)->delete();

        return $user;
    }

    private function enableMaintenance(User $tenant): void
    {
        GeneralSetting::where('user_id', $tenant->id)->delete();
        GeneralSetting::create([
            'user_id' => $tenant->id,
            'maintenance_mode' => 1,
        ]);
    }

    private function seedWebsiteData(User $tenant): void
    {
        if (! Schema::hasTable('tenant_pages')
            || ! Schema::hasTable('tenant_global_components')
            || ! Schema::hasTable('tenant_website_layouts')) {
            return;
        }

        TenantPage::create([
            'id' => (string) Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'homepage',
            'components' => [],
        ]);
        TenantGlobalComponent::create([
            'id' => (string) Str::uuid(),
            'user_id' => $tenant->id,
            'data' => ['header' => []],
        ]);
        TenantWebsiteLayout::create([
            'id' => (string) Str::uuid(),
            'user_id' => $tenant->id,
            'data' => ['currentTheme' => 1],
        ]);
    }
}

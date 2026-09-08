<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Http\Middleware\SetTenantForPermissions;
use App\Models\Api\GeneralSetting;
use App\Models\Membership;
use App\Models\Package;
use App\Models\TenantGlobalComponent;
use App\Models\TenantPage;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
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

        $this->guardSchemaRepairs();
        $this->ensureApiGeneralSettingsTable();
        $this->ensureApiDomainsSettingsTable();
        $this->ensureTenantPagesTable();
        $this->ensureGetTenantSupportTables();
        $this->ensureMembershipTables();
        $this->withoutMiddleware(SetTenantForPermissions::class);
    }

    private function guardSchemaRepairs(): void
    {
        $connection = $this->schemaConnection();

        if (! app()->environment('testing') || $connection->getDriverName() !== 'sqlite') {
            $this->fail('Tenant site maintenance schema repairs are only allowed in the SQLite testing environment.');
        }
    }

    private function ensureApiGeneralSettingsTable(): void
    {
        if (! Schema::hasTable('api_general_settings')) {
            Schema::create('api_general_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->boolean('maintenance_mode')->default(false);
                $table->timestamps();
            });
        }

        $columns = [
            'user_id' => fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable(),
            'maintenance_mode' => fn (Blueprint $table) => $table->boolean('maintenance_mode')->default(false),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('api_general_settings', $column)) {
                Schema::table('api_general_settings', $definition);
            }
        }
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

    private function ensureGetTenantSupportTables(): void
    {
        if (! Schema::hasTable('user_basic_settings')) {
            Schema::create('user_basic_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('company_name')->nullable();
                $table->string('logo')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_settings')) {
            Schema::create('tenant_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id')->index();
                $table->json('settings')->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_static_pages')) {
            Schema::create('tenant_static_pages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('page_id');
                $table->json('components')->nullable();
                $table->string('url')->nullable();
                $table->json('published_data')->nullable();
                $table->timestamps();
            });
        }
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

    public function test_get_tenant_includes_public_subscription_and_website_access(): void
    {
        $tenant = $this->createTenant('maint-public');
        $this->seedPackage(16, 'Free', 'yearly', 0);
        $this->seedPackage(26, 'Trial', 'trial', 0, 1, 7);
        $expiredTrial = $this->createMembership($tenant, 26, [
            'start_date' => now()->subDays(8)->toDateString(),
            'expire_date' => now()->subDay()->toDateString(),
            'is_trial' => 1,
            'trial_days' => 7,
        ]);
        $this->createMembership($tenant, 16, [
            'payment_method' => 'system',
            'activation_source' => 'expiration_fallback',
            'transition_reason' => 'trial_expired',
            'previous_membership_id' => $expiredTrial->id,
        ]);
        $this->enableMaintenance($tenant);
        $this->seedWebsiteData($tenant);

        $this->postJson('/api/v1/tenant-website/getTenant', [
            'websiteName' => $tenant->username,
        ])
            ->assertOk()
            ->assertJsonMissingPath('subscription.previous_membership_id')
            ->assertJsonPath('subscription.schema_version', 1)
            ->assertJsonPath('subscription.plan_type', 'free')
            ->assertJsonPath('subscription.premium_access', false)
            ->assertJsonPath('subscription.transition_reason', 'trial_expired')
            ->assertJsonPath('website_access.allowed', false)
            ->assertJsonPath('website_access.reason', 'subscription_required');
    }

    public function test_maintenance_response_includes_stable_code_and_shared_reason(): void
    {
        $tenant = $this->createTenant('maint-503');
        $this->seedPackage(16, 'Free', 'yearly', 0);
        $this->createMembership($tenant, 16, [
            'payment_method' => 'system',
            'activation_source' => 'expiration_fallback',
            'transition_reason' => 'paid_expired',
        ]);
        $this->enableMaintenance($tenant);

        $this->getJson($this->pagesUrl($tenant))
            ->assertStatus(503)
            ->assertJsonPath('maintenance', true)
            ->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED')
            ->assertJsonPath('website_access.allowed', false)
            ->assertJsonPath('website_access.reason', 'subscription_required');
    }

    public function test_manual_maintenance_returns_manual_reason(): void
    {
        $tenant = $this->createTenant('maint-manual');
        $this->seedPackage(25, 'Paid', 'monthly', 50);
        $this->createMembership($tenant, 25, [
            'payment_method' => 'arb',
            'price' => 50,
        ]);
        $this->enableMaintenance($tenant);

        $this->getJson($this->pagesUrl($tenant))
            ->assertStatus(503)
            ->assertJsonPath('code', 'SITE_MAINTENANCE')
            ->assertJsonPath('website_access.reason', 'manual_maintenance');
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

    private function ensureMembershipTables(): void
    {
        if (! Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('title_en')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->string('term')->nullable();
                $table->unsignedTinyInteger('is_trial')->default(0);
                $table->unsignedInteger('trial_days')->default(0);
                $table->string('status')->default('1');
                $table->text('features')->nullable();
                $table->unsignedInteger('video_size_limit')->default(0);
                $table->unsignedInteger('file_size_limit')->default(0);
                $table->unsignedInteger('number_of_vcards')->default(0);
                $table->unsignedInteger('project_limit_number')->default(0);
                $table->unsignedInteger('real_estate_limit_number')->default(0);
                $table->unsignedInteger('whatsapp_numbers_limit')->default(0);
                $table->unsignedInteger('employees_limit')->default(0);
                $table->timestamps();
            });
        }

        $this->ensurePackageColumns();

        if (! Schema::hasTable('memberships')) {
            Schema::create('memberships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('package_id')->nullable();
                $table->decimal('package_price', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('price', 10, 2)->default(0);
                $table->string('currency')->nullable();
                $table->string('currency_symbol')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->unsignedTinyInteger('is_trial')->default(0);
                $table->unsignedInteger('trial_days')->default(0);
                $table->date('start_date')->nullable();
                $table->date('expire_date')->nullable();
                $table->unsignedTinyInteger('modified')->default(0);
                $table->string('activation_source')->nullable();
                $table->string('transition_reason')->nullable();
                $table->unsignedBigInteger('previous_membership_id')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureMembershipColumns();
    }

    private function ensurePackageColumns(): void
    {
        $columns = [
            'title_en' => fn (Blueprint $table) => $table->string('title_en')->nullable(),
            'is_trial' => fn (Blueprint $table) => $table->unsignedTinyInteger('is_trial')->default(0),
            'trial_days' => fn (Blueprint $table) => $table->unsignedInteger('trial_days')->default(0),
            'status' => fn (Blueprint $table) => $table->string('status')->default('1'),
            'features' => fn (Blueprint $table) => $table->text('features')->nullable(),
            'video_size_limit' => fn (Blueprint $table) => $table->unsignedInteger('video_size_limit')->default(0),
            'file_size_limit' => fn (Blueprint $table) => $table->unsignedInteger('file_size_limit')->default(0),
            'number_of_vcards' => fn (Blueprint $table) => $table->unsignedInteger('number_of_vcards')->default(0),
            'project_limit_number' => fn (Blueprint $table) => $table->unsignedInteger('project_limit_number')->default(0),
            'real_estate_limit_number' => fn (Blueprint $table) => $table->unsignedInteger('real_estate_limit_number')->default(0),
            'whatsapp_numbers_limit' => fn (Blueprint $table) => $table->unsignedInteger('whatsapp_numbers_limit')->default(0),
            'employees_limit' => fn (Blueprint $table) => $table->unsignedInteger('employees_limit')->default(0),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('packages', $column)) {
                Schema::table('packages', $definition);
            }
        }
    }

    private function ensureMembershipColumns(): void
    {
        $columns = [
            'coupon_code' => fn (Blueprint $table) => $table->string('coupon_code')->nullable(),
            'currency' => fn (Blueprint $table) => $table->string('currency')->nullable(),
            'currency_symbol' => fn (Blueprint $table) => $table->string('currency_symbol')->nullable(),
            'settings' => fn (Blueprint $table) => $table->text('settings')->nullable(),
            'modified' => fn (Blueprint $table) => $table->unsignedTinyInteger('modified')->default(0),
            'activation_source' => fn (Blueprint $table) => $table->string('activation_source')->nullable(),
            'transition_reason' => fn (Blueprint $table) => $table->string('transition_reason')->nullable(),
            'previous_membership_id' => fn (Blueprint $table) => $table->unsignedBigInteger('previous_membership_id')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('memberships', $column)) {
                Schema::table('memberships', $definition);
            }
        }
    }

    private function seedPackage(int $id, string $title, string $term, float $price, int $isTrial = 0, int $trialDays = 0): void
    {
        $package = Package::query()->find($id) ?? new Package();
        $package->id = $id;
        $package->title = $title;
        $package->title_en = $title;
        $package->price = $price;
        $package->term = $term;
        $package->is_trial = $isTrial;
        $package->trial_days = $trialDays;
        $package->status = '1';
        $package->features = json_encode([]);
        $package->save();
    }

    private function createMembership(User $user, int $packageId, array $overrides = []): Membership
    {
        return Membership::create(array_merge([
            'user_id' => $user->id,
            'package_id' => $packageId,
            'package_price' => 0,
            'discount' => 0,
            'price' => 0,
            'currency' => 'SAR',
            'currency_symbol' => 'SAR',
            'payment_method' => 'system',
            'transaction_id' => uniqid('test_', true),
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
        ], $overrides));
    }

    private function schemaConnection(): ConnectionInterface
    {
        return Schema::getConnection();
    }
}

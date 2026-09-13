<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureSqliteTestingSchema();

        $uses = class_uses_recursive(static::class) ?: [];
        $usesRefreshDatabase = in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, $uses, true);

        if (! $usesRefreshDatabase) {
            return;
        }

        $dbName = (string) config('database.connections.' . config('database.default') . '.database');
        $allowed = (string) env('ALLOW_REAL_DB_TESTS', '0') === '1';

        if ($allowed) {
            return;
        }

        throw new \RuntimeException(
            "This test uses RefreshDatabase but ALLOW_REAL_DB_TESTS is not enabled. " .
            "Refusing to run because it may wipe a real database. Current DB: '{$dbName}'."
        );
    }

    private function ensureSqliteTestingSchema(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = (string) config('database.connections.sqlite.database');

        if ($database !== ':memory:' && $database !== '') {
            $directory = dirname($database);
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            if (! file_exists($database)) {
                touch($database);
            }
        }

        $this->ensureUsersTable();
        $this->ensureUserCustomDomainsTable();
        $this->ensureApiDomainSettingsTable();
        $this->ensurePersonalAccessTokensTable();
        $this->ensurePermissionTables();
        $this->ensureActivityLogTables();
    }

    private function ensureUsersTable(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('referred_by')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->unique();
                $table->string('username')->unique();
                $table->string('photo')->nullable();
                $table->string('company_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('address')->nullable();
                $table->string('country')->nullable();
                $table->boolean('email_verified')->default(false);
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('phone_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->unsignedTinyInteger('status')->default(1);
                $table->string('account_type')->default('tenant');
                $table->boolean('active')->default(true);
                $table->boolean('featured')->default(false);
                $table->boolean('online_status')->default(false);
                $table->boolean('subscribed')->default(false);
                $table->decimal('subscription_amount', 10, 2)->default(0);
                $table->timestamp('trial_ends_at')->nullable();
                $table->unsignedInteger('rbac_version')->default(0);
                $table->timestamp('rbac_seeded_at')->nullable();
                $table->string('referral_code')->nullable();
                $table->string('referral_id')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    private function ensureUserCustomDomainsTable(): void
    {
        if (! Schema::hasTable('user_custom_domains')) {
            Schema::create('user_custom_domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('requested_domain')->nullable();
                $table->string('current_domain')->nullable();
                $table->boolean('status')->default(false);
                $table->timestamps();
            });
        }
    }

    private function ensureApiDomainSettingsTable(): void
    {
        if (! Schema::hasTable('api_domains_settings')) {
            Schema::create('api_domains_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('custom_domain_id')->nullable()->constrained('user_custom_domains')->nullOnDelete();
                $table->string('custom_name')->unique();
                $table->string('dns_mode', 32)->default('vercel_ns');
                $table->string('registrar', 100)->nullable();
                $table->enum('status', ['pending', 'active', 'failed'])->default('pending');
                $table->boolean('primary')->default(false);
                $table->boolean('ssl')->default(false);
                $table->boolean('auto_renewal')->default(false);
                $table->date('added_date');
                $table->date('expires_at')->nullable();
                $table->json('dns_records')->nullable();
                $table->timestamps();
            });

            return;
        }

        $columns = [
            'custom_domain_id' => fn (Blueprint $table) => $table->unsignedBigInteger('custom_domain_id')->nullable()->after('user_id'),
            'dns_mode' => fn (Blueprint $table) => $table->string('dns_mode', 32)->default('vercel_ns')->after('custom_name'),
            'registrar' => fn (Blueprint $table) => $table->string('registrar', 100)->nullable()->after('dns_mode'),
            'auto_renewal' => fn (Blueprint $table) => $table->boolean('auto_renewal')->default(false)->after('ssl'),
            'expires_at' => fn (Blueprint $table) => $table->date('expires_at')->nullable()->after('added_date'),
            'dns_records' => fn (Blueprint $table) => $table->json('dns_records')->nullable()->after('expires_at'),
        ];

        foreach ($columns as $column => $callback) {
            if (! Schema::hasColumn('api_domains_settings', $column)) {
                Schema::table('api_domains_settings', function (Blueprint $table) use ($callback) {
                    $callback($table);
                });
            }
        }

        if (Schema::hasColumn('api_domains_settings', 'custom_domain_id')) {
            try {
                DB::statement('CREATE INDEX api_domains_settings_custom_domain_id_index ON api_domains_settings (custom_domain_id)');
            } catch (\Throwable $e) {
                // Ignore duplicate index creation across test cases.
            }
        }
    }

    private function ensurePersonalAccessTokensTable(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function ensurePermissionTables(): void
    {
        if (! Schema::hasTable('api_permissions')) {
            Schema::create('api_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->unique(['name', 'guard_name', 'team_id'], 'api_permissions_name_guard_team_unique');
            });
        }

        if (! Schema::hasTable('api_roles')) {
            Schema::create('api_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->unique(['name', 'guard_name', 'team_id'], 'api_roles_name_guard_team_unique');
            });
        }

        if (! Schema::hasTable('api_role_has_permissions')) {
            Schema::create('api_role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('team_id')->nullable();
                $table->primary(['permission_id', 'role_id', 'team_id'], 'api_role_has_permissions_primary');
            });
        }

        if (! Schema::hasTable('api_model_has_roles')) {
            Schema::create('api_model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->unsignedBigInteger('team_id')->nullable();
                $table->index(['model_id', 'model_type'], 'api_model_has_roles_model_index');
                $table->index(['team_id'], 'api_model_has_roles_team_index');
            });
        }

        if (! Schema::hasTable('api_model_has_permissions')) {
            Schema::create('api_model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->unsignedBigInteger('team_id')->nullable();
                $table->index(['model_id', 'model_type'], 'api_model_has_permissions_model_index');
                $table->index(['team_id'], 'api_model_has_permissions_team_index');
            });
        }
    }

    private function ensureActivityLogTables(): void
    {
        if (! Schema::hasTable('api_employee_activity_logs')) {
            Schema::create('api_employee_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('actor_type')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action');
                $table->string('target_type')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }
}

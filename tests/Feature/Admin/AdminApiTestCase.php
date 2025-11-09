<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class AdminApiTestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * Ensure shared admin tables exist before running tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensurePasswordResetsTable();
        $this->ensureRolesTable();
        $this->resetAdminData();
    }

    /**
     * Authenticate a freshly created admin for the admin API guard.
     */
    protected function signInAdmin(array $attributes = []): Admin
    {
        $admin = Admin::factory()->create($attributes);

        Sanctum::actingAs($admin, ['*'], config('admin-api.guard'));

        return $admin;
    }

    /**
     * Ensure the password_resets table exists for auth flows.
     */
    private function ensurePasswordResetsTable(): void
    {
        if (Schema::hasTable('password_resets')) {
            return;
        }

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Ensure the roles table exists so admin relations can load.
     */
    private function ensureRolesTable(): void
    {
        if (Schema::hasTable('roles')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reset admin-related tables between tests to avoid unique constraint collisions.
     */
    private function resetAdminData(): void
    {
        if (!Schema::hasTable('admins')) {
            return;
        }

        Schema::disableForeignKeyConstraints();
        DB::table('admins')->truncate();

        if (Schema::hasTable('password_resets')) {
            DB::table('password_resets')->truncate();
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }
}


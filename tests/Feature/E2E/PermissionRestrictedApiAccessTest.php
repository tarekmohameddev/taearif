<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * E2E: Permission-restricted API access (can:*).
 * Failure: employee without permission receives 403.
 * Success: same employee with permission receives 200.
 */
class PermissionRestrictedApiAccessTest extends ApiE2ETestCase
{
    /**
     * Normalize response exceptions to Throwable objects to avoid PHPUnit errors on string entries.
     */
    private function normalizeResponseExceptions($response): void
    {
        if (!isset($response->exceptions) || $response->exceptions === null) {
            return;
        }

        $exceptions = $response->exceptions;

        if ($exceptions instanceof \Illuminate\Support\Collection) {
            $response->exceptions = $exceptions
                ->filter(fn ($item) => $item instanceof \Throwable)
                ->values();
            return;
        }

        if (is_array($exceptions)) {
            $response->exceptions = collect($exceptions)
                ->filter(fn ($item) => $item instanceof \Throwable)
                ->values();
            return;
        }

        if (is_string($exceptions)) {
            $response->exceptions = collect();
        }
    }

    private function skipIfMissingSchema(): void
    {
        $required = [
            'users',
            'api_permissions',
            'api_model_has_permissions',
            'project_logs',
        ];

        foreach ($required as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}. Restore taearif_testing from dump.");
            }
        }
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function createTenantAndEmployee(): array
    {
        try {
            $tenant = User::factory()->create([
                'account_type' => 'tenant',
                'active' => true,
                'status' => 1,
            ]);
            $employee = User::factory()->create([
                'account_type' => 'employee',
                'tenant_id' => $tenant->id,
                'email' => 'e2e-perm-employee@example.com',
                'password' => Hash::make('password123'),
                'active' => true,
                'status' => 1,
            ]);

            return [$tenant, $employee];
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false || strpos($msg, 'Unknown column') !== false) {
                $this->markTestSkipped('Users table or schema missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }
    }

    private function grantPermission(User $tenant, User $employee, string $permissionName): void
    {
        try {
            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId((int) $tenant->id);
            $registrar->forgetCachedPermissions();

            try {
                $permission = Permission::findByName($permissionName, 'sanctum');
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'sanctum',
                    'team_id' => $tenant->id,
                ]);
            }

            $employee->givePermissionTo($permission);
            $registrar->forgetCachedPermissions();
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false || strpos($msg, 'Unknown column') !== false) {
                $this->markTestSkipped('RBAC tables or columns missing. Restore taearif_testing from dump.');
            }
            throw $e;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false || strpos($msg, 'Unknown column') !== false) {
                $this->markTestSkipped('RBAC tables or columns missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }
    }

    /** @test */
    public function employee_permission_gate_blocks_then_allows_same_endpoint(): void
    {
        $this->skipIfMissingSchema();

        try {
            [$tenant, $employee] = $this->createTenantAndEmployee();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false || strpos($msg, 'Unknown column') !== false) {
                $this->markTestSkipped('Users or RBAC schema missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }

        $endpoint = '/api/v1/projects/1/logs';

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $tenant->id);
        Sanctum::actingAs($employee);

        $denied = $this->getJson($endpoint);
        $this->normalizeResponseExceptions($denied);
        $denied->assertStatus(403)
            ->assertJsonStructure(['message']);

        try {
            $this->grantPermission($tenant, $employee, 'projects.view');
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false || strpos($msg, 'Unknown column') !== false) {
                $this->markTestSkipped('RBAC tables or relations missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }

        // Clear permission cache so the next request reflects the grant.
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Sanctum::actingAs($employee);

        $allowed = $this->getJson($endpoint);
        $this->normalizeResponseExceptions($allowed);

        $allowed->assertOk()
            ->assertJsonStructure(['status', 'data']);
    }
}

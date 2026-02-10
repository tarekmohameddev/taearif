<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * E2E: RBAC role assignment at login time.
 * 
 * Business rule: A user's role determines access to role-protected endpoints.
 * Role must be resolved correctly at authentication time.
 * 
 * Scenario:
 * 1) An authenticated user WITHOUT the required role calls a role-protected endpoint → receives 403.
 * 2) The SAME user is assigned a role that grants access.
 * 3) The user logs in again (new token).
 * 4) The SAME endpoint is called → receives 200.
 */
class RbacRoleAssignmentAtLoginTest extends ApiE2ETestCase
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
            'api_roles',
            'api_model_has_permissions',
            'api_model_has_roles',
            'api_role_has_permissions',
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
                'email' => 'e2e-rbac-role-employee@example.com',
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

    private function createRoleWithPermission(User $tenant, string $roleName, string $permissionName): Role
    {
        try {
            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId((int) $tenant->id);
            $registrar->forgetCachedPermissions();

            // Create or get the permission
            try {
                $permission = Permission::findByName($permissionName, 'sanctum');
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'sanctum',
                    'team_id' => $tenant->id,
                ]);
            }

            // Create the role
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => 'sanctum',
                'team_id' => $tenant->id,
            ]);

            // Assign permission to role
            $role->givePermissionTo($permission);

            $registrar->forgetCachedPermissions();

            return $role;
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

    private function assignRoleToUser(User $tenant, User $employee, Role $role): void
    {
        try {
            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId((int) $tenant->id);
            $registrar->forgetCachedPermissions();

            $employee->assignRole($role);

            $registrar->forgetCachedPermissions();
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false || strpos($msg, 'Unknown column') !== false) {
                $this->markTestSkipped('RBAC tables or relations missing. Restore taearif_testing from dump.');
            }
            throw $e;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false || strpos($msg, 'Unknown column') !== false) {
                $this->markTestSkipped('RBAC tables or relations missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }
    }

    /** @test */
    public function user_role_determines_access_after_login(): void
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

        // Endpoint protected by projects.view permission
        $endpoint = '/api/v1/projects/1/logs';

        // Step 1: Login as employee (without role/permission)
        // Ensure default auth guard is web for login
        config(['auth.defaults.guard' => 'web']);
        
        $this->fakeRecaptcha();
        $loginResponse1 = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $employee->email,
            'password' => 'password123',
        ]);
        $this->normalizeResponseExceptions($loginResponse1);

        $loginResponse1->assertOk()
            ->assertJsonStructure(['user', 'token']);

        $token1 = $loginResponse1->json('token');

        // Step 2: Call endpoint without required permission → expect 403
        $denied = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json',
        ])->getJson($endpoint);
        $this->normalizeResponseExceptions($denied);
        $denied->assertStatus(403)
            ->assertJsonStructure(['message']);

        // Step 3: Create role with required permission
        try {
            $role = $this->createRoleWithPermission($tenant, 'project-viewer', 'projects.view');
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false || strpos($msg, 'Unknown column') !== false) {
                $this->markTestSkipped('RBAC tables or relations missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }

        // Step 4: Assign role to employee
        try {
            $this->assignRoleToUser($tenant, $employee, $role);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false || strpos($msg, 'Unknown column') !== false) {
                $this->markTestSkipped('RBAC tables or relations missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }

        // Step 5: Login again to get new token (roles are evaluated at authentication time)
        // Reset auth state and ensure default guard is web for login
        app('auth')->forgetGuards();
        config(['auth.defaults.guard' => 'web']);
        
        $this->fakeRecaptcha();
        $loginResponse2 = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $employee->email,
            'password' => 'password123',
        ]);
        $this->normalizeResponseExceptions($loginResponse2);

        $loginResponse2->assertOk()
            ->assertJsonStructure(['user', 'token']);

        $token2 = $loginResponse2->json('token');

        // Step 6: Call the same endpoint with new token → expect 200
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allowed = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
            'Accept' => 'application/json',
        ])->getJson($endpoint);
        $this->normalizeResponseExceptions($allowed);

        $allowed->assertOk()
            ->assertJsonStructure(['status', 'data']);
    }
}

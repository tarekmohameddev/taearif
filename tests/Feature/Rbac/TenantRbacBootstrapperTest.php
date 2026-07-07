<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Models\User;
use App\Services\TenantRbacBootstrapper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantRbacBootstrapperTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_missing_permissions_globally_even_when_tenant_context_is_used(): void
    {
        $permName = 'test.rbac.global_only';

        config([
            'rbac.guard' => 'sanctum',
            'rbac.rbac_version' => 1,
            'rbac.permissions' => [$permName],
            'rbac.role_templates' => [
                'manager' => [$permName],
            ],
        ]);

        /** @var User $tenant */
        $tenant = User::factory()->tenant()->create([
            'rbac_version' => 0,
            'rbac_seeded_at' => null,
        ]);

        // Create a tenant-scoped permission with the same name to simulate polluted data.
        Permission::query()->where('name', $permName)->where('guard_name', 'sanctum')->delete();
        Permission::create([
            'name' => $permName,
            'guard_name' => 'sanctum',
            'team_id' => $tenant->id,
        ]);

        app(TenantRbacBootstrapper::class)->run((int) $tenant->id);

        $global = Permission::query()
            ->where('name', $permName)
            ->where('guard_name', 'sanctum')
            ->whereNull('team_id')
            ->first();

        $this->assertNotNull($global, 'Expected a GLOBAL permission row (team_id NULL) to be created.');

        $managerRole = Role::query()
            ->where('name', 'manager')
            ->where('guard_name', 'sanctum')
            ->where('team_id', $tenant->id)
            ->first();

        $this->assertNotNull($managerRole, 'Expected manager role to be created for tenant.');

        $linked = DB::table('api_role_has_permissions as rhp')
            ->join('api_permissions as p', 'p.id', '=', 'rhp.permission_id')
            ->where('rhp.role_id', $managerRole->id)
            ->where('p.name', $permName)
            ->select('p.id', 'p.team_id')
            ->get();

        $this->assertNotEmpty($linked, 'Expected manager role to be linked to some permission row for this name.');

        $hasGlobal = $linked->contains(fn ($row) => $row->team_id === null);
        $this->assertTrue($hasGlobal, 'Expected manager role to be linked to the GLOBAL permission row (team_id NULL).');
    }

    public function test_it_fails_cleanly_when_permissions_remain_missing_after_seeding(): void
    {
        $permName = 'test.rbac.missing_perm';

        config([
            'rbac.guard' => 'sanctum',
            'rbac.rbac_version' => 1,
            'rbac.permissions' => [$permName],
            'rbac.role_templates' => [
                'manager' => [$permName],
            ],
        ]);

        /** @var User $tenant */
        $tenant = User::factory()->tenant()->create([
            'rbac_version' => 0,
            'rbac_seeded_at' => null,
        ]);

        // Prevent creating the permission by making the table effectively unwritable is hard in a unit test;
        // instead, simulate "still missing" by deleting after run and re-running with a forced missing check.
        // This ensures the error type and message shape stay stable if creation fails in real deployments.
        Permission::query()
            ->where('name', $permName)
            ->where('guard_name', 'sanctum')
            ->delete();

        $this->expectException(\App\Exceptions\Api\BusinessLogicException::class);

        // Force the missing check path by mocking ensureGlobalPermissions to return empty.
        $bootstrapper = $this->partialMock(TenantRbacBootstrapper::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('ensureGlobalPermissions')->andReturn(collect());
        });

        $bootstrapper->run((int) $tenant->id);
    }
}


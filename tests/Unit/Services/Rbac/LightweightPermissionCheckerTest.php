<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Rbac;

use App\Models\User;
use App\Services\Rbac\LightweightPermissionChecker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LightweightPermissionCheckerTest extends TestCase
{
    private LightweightPermissionChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new LightweightPermissionChecker();
    }

    public function test_tenant_owner_always_has_permission(): void
    {
        $user = $this->makeTenant(42);

        $this->assertTrue($this->checker->userHasPermission($user, 'contact_messages.view'));
        $this->assertTrue($this->checker->userHasPermission($user, 'anything.at.all'));
    }

    public function test_empty_permission_returns_false(): void
    {
        $user = $this->makeTenant(1);

        $this->assertFalse($this->checker->userHasPermission($user, ''));
    }

    public function test_policy_abilities_return_false(): void
    {
        $user = $this->makeTenant(1);

        foreach (['control', 'disable', 'enable', 'toggle'] as $ability) {
            $this->assertFalse(
                $this->checker->userHasPermission($user, $ability),
                "Expected false for policy ability '{$ability}'"
            );
        }
    }

    public function test_employee_with_permission_via_cache_returns_true(): void
    {
        $employee = $this->makeEmployee(userId: 10, tenantId: 5);

        Cache::shouldReceive('remember')
            ->once()
            ->with('rbac:perm_names:10:5', 120, \Mockery::type('Closure'))
            ->andReturn(['contact_messages.view', 'customers.view']);

        $this->assertTrue($this->checker->userHasPermission($employee, 'contact_messages.view'));
    }

    public function test_employee_without_permission_returns_false(): void
    {
        $employee = $this->makeEmployee(userId: 10, tenantId: 5);

        Cache::shouldReceive('remember')
            ->once()
            ->with('rbac:perm_names:10:5', 120, \Mockery::type('Closure'))
            ->andReturn(['customers.view']);

        $this->assertFalse($this->checker->userHasPermission($employee, 'contact_messages.view'));
    }

    public function test_forget_for_removes_cache_key(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('rbac:perm_names:7:3');

        LightweightPermissionChecker::forgetFor(7, 3);
    }

    public function test_permission_names_for_uses_cache_with_correct_key(): void
    {
        $employee = $this->makeEmployee(userId: 20, tenantId: 9);

        Cache::shouldReceive('remember')
            ->once()
            ->with('rbac:perm_names:20:9', 120, \Mockery::type('Closure'))
            ->andReturn(['properties.view', 'contact_messages.view']);

        $names = $this->checker->permissionNamesFor($employee);

        $this->assertContains('properties.view', $names);
        $this->assertContains('contact_messages.view', $names);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTenant(int $id): User
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = $id;
        $user->account_type = 'tenant';
        $user->tenant_id = null;

        $user->shouldReceive('isTenant')->andReturn(true);
        $user->shouldReceive('isEmployee')->andReturn(false);
        $user->shouldReceive('tenantOwnerId')->andReturn($id);
        $user->shouldReceive('getMorphClass')->andReturn(User::class);

        return $user;
    }

    private function makeEmployee(int $userId, int $tenantId): User
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = $userId;
        $user->account_type = 'employee';
        $user->tenant_id = $tenantId;

        $user->shouldReceive('isTenant')->andReturn(false);
        $user->shouldReceive('isEmployee')->andReturn(true);
        $user->shouldReceive('tenantOwnerId')->andReturn($tenantId);
        $user->shouldReceive('getMorphClass')->andReturn(User::class);

        return $user;
    }
}

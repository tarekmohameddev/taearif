<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;

/**
 * E2E: Employee login under tenant (same POST /api/login).
 * Happy path: employee with active tenant → 200, user + token.
 * Failure path: employee with inactive tenant → 403, "Tenant is inactive; employee login disabled".
 */
class EmployeeLoginUnderTenantTest extends ApiE2ETestCase
{
    /**
     * Create tenant and employee; skip if users table or required columns are missing.
     *
     * @return array{0: User, 1: User}
     */
    private function createTenantAndEmployee(bool $tenantActive = true): array
    {
        try {
            $tenant = User::factory()->create([
                'account_type' => 'tenant',
                'active' => $tenantActive,
                'status' => $tenantActive ? 1 : 0,
            ]);
            $employee = User::factory()->create([
                'account_type' => 'employee',
                'tenant_id' => $tenant->id,
                'email' => 'e2e-employee@example.com',
                'password' => Hash::make('password'),
                'active' => true,
                'status' => 1,
            ]);
            return [$tenant, $employee];
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false) {
                $this->markTestSkipped('users table or schema missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }
    }

    /** @test */
    public function employee_with_active_tenant_can_login(): void
    {
        $this->fakeRecaptcha();
        [$tenant, $employee] = $this->createTenantAndEmployee(true);

        $response = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $employee->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', $employee->email);
    }

    /** @test */
    public function employee_with_inactive_tenant_receives_403(): void
    {
        $this->fakeRecaptcha();
        [$tenant, $employee] = $this->createTenantAndEmployee(false);

        $response = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $employee->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Tenant is inactive; employee login disabled');
    }
}

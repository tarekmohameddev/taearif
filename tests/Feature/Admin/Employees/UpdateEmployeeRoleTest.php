<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Employees;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\Role;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateEmployeeRoleTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_an_employee_role(): void
    {
        $fromRole = Role::factory()->create(['name' => 'Support Agent']);
        $toRole = Role::factory()->create(['name' => 'Operations Manager']);

        $employee = Admin::factory()->create([
            'role_id' => $fromRole->id,
        ]);

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.employees.role.update', $employee->uuid),
            [
                'role_id' => $toRole->id,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.role.id', $toRole->id)
            ->assertJsonPath('data.role.name', 'Operations Manager');

        $this->assertDatabaseHas('admins', [
            'id' => $employee->id,
            'role_id' => $toRole->id,
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_role_payload(): void
    {
        $role = Role::factory()->create();
        $employee = Admin::factory()->create([
            'role_id' => $role->id,
        ]);

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.employees.role.update', $employee->uuid),
            [
                'role_id' => 'invalid',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $role = Role::factory()->create();
        $employee = Admin::factory()->create([
            'role_id' => $role->id,
        ]);

        $this->putJson(
            route('admin.api.employees.role.update', $employee->uuid),
            [
                'role_id' => $role->id,
            ]
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_employee_does_not_exist_for_role_update(): void
    {
        $role = Role::factory()->create();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.employees.role.update', (string) Str::uuid()),
            [
                'role_id' => $role->id,
            ]
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}


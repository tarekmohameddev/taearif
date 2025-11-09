<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Employees;

use App\Domain\Admin\Models\Admin;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateEmployeeTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_an_employee(): void
    {
        $employee = Admin::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Employee',
            'username' => 'original.employee',
            'email' => 'original@example.com',
            'status' => true,
        ]);

        $this->signInAdmin();

        $payload = [
            'first_name' => 'Updated',
            'last_name' => 'Employee',
            'username' => 'updated.employee',
            'email' => 'updated@example.com',
            'status' => true,
        ];

        $response = $this->putJson(
            route('admin.api.employees.update', $employee->uuid),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.first_name', 'Updated')
            ->assertJsonPath('data.last_name', 'Employee')
            ->assertJsonPath('data.username', 'updated.employee')
            ->assertJsonPath('data.email', 'updated@example.com')
            ->assertJsonPath('data.status', true);

        $this->assertDatabaseHas('admins', [
            'id' => $employee->id,
            'first_name' => 'Updated',
            'last_name' => 'Employee',
            'username' => 'updated.employee',
            'email' => 'updated@example.com',
            'status' => 1,
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_payload(): void
    {
        $employee = Admin::factory()->create();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.employees.update', $employee->uuid),
            ['email' => 'not-an-email']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $employee = Admin::factory()->create();

        $response = $this->putJson(
            route('admin.api.employees.update', $employee->uuid),
            ['first_name' => 'Attempted']
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_employee_does_not_exist(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.employees.update', 'missing-uuid'),
            ['first_name' => 'Updated']
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}


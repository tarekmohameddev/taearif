<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Employees;

use App\Domain\Admin\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateEmployeePasswordTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_an_employee_password(): void
    {
        $employee = Admin::factory()->create([
            'password' => Hash::make('OldPassword1!'),
        ]);

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.employees.password.update', $employee->uuid),
            [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.uuid', $employee->uuid)
            ->assertJsonPath('data.status', (bool) $employee->status);

        $this->assertTrue(
            Hash::check('NewPassword123!', $employee->fresh()->password),
            'Employee password was not updated.'
        );
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_password_payload(): void
    {
        $employee = Admin::factory()->create();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.employees.password.update', $employee->uuid),
            [
                'password' => 'short',
                'password_confirmation' => 'short',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $employee = Admin::factory()->create();

        $this->putJson(
            route('admin.api.employees.password.update', $employee->uuid),
            [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_employee_does_not_exist_for_password_update(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.employees.password.update', (string) Str::uuid()),
            [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}


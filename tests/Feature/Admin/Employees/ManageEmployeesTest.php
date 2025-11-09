<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Employees;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageEmployeesTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_employees(): void
    {
        $this->signInAdmin();

        $role = Role::factory()->create();

        $employees = Admin::factory()->count(2)->create([
            'role_id' => $role->id,
        ]);

        $response = $this->getJson(route('admin.api.employees.index'));

        $response->assertOk()
            ->assertJsonFragment(['uuid' => $employees->first()->uuid])
            ->assertJsonFragment(['uuid' => $employees->last()->uuid]);
    }

    /** @test */
    public function listing_employees_requires_authentication(): void
    {
        $this->getJson(route('admin.api.employees.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_create_an_employee(): void
    {
        Storage::fake('public');

        $this->signInAdmin();

        $role = Role::factory()->create();

        $payload = [
            'username' => 'employee_' . Str::random(5),
            'email' => 'employee@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Admin',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'role_id' => $role->id,
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ];

        $response = $this->postJson(route('admin.api.employees.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'employee@example.com')
            ->assertJsonPath('data.role.id', $role->id);

        $employee = Admin::where('email', 'employee@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Secret123!', $employee->password));
    }

    /** @test */
    public function validation_errors_are_returned_when_creating_employee_with_invalid_payload(): void
    {
        $this->signInAdmin();

        $this->postJson(route('admin.api.employees.store'), [
            'username' => '',
            'email' => 'not-an-email',
            'first_name' => '',
            'last_name' => '',
            'password' => 'short',
            'password_confirmation' => 'different',
            'role_id' => 999,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'username',
                'email',
                'first_name',
                'last_name',
                'password',
                'role_id',
            ]);
    }

    /** @test */
    public function admin_can_view_an_employee(): void
    {
        $this->signInAdmin();

        $role = Role::factory()->create();

        $employee = Admin::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this->getJson(
            route('admin.api.employees.show', $employee->uuid)
        );

        $response->assertOk()
            ->assertJsonPath('data.uuid', $employee->uuid)
            ->assertJsonPath('data.role.id', $role->id);
    }

    /** @test */
    public function viewing_employee_requires_authentication(): void
    {
        $employee = Admin::factory()->create();

        $this->getJson(
            route('admin.api.employees.show', $employee->uuid)
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_viewing_missing_employee(): void
    {
        $this->signInAdmin();

        $this->getJson(
            route('admin.api.employees.show', (string) Str::uuid())
        )->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    /** @test */
    public function admin_can_delete_an_employee(): void
    {
        $this->signInAdmin();

        $employee = Admin::factory()->create();

        $response = $this->deleteJson(
            route('admin.api.employees.destroy', $employee->uuid)
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('admins', [
            'uuid' => $employee->uuid,
        ]);
    }

    /** @test */
    public function deleting_employee_requires_authentication(): void
    {
        $employee = Admin::factory()->create();

        $this->deleteJson(
            route('admin.api.employees.destroy', $employee->uuid)
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_toggle_employee_status(): void
    {
        $this->signInAdmin();

        $employee = Admin::factory()->create([
            'status' => true,
        ]);

        $response = $this->postJson(
            route('admin.api.employees.toggle-status', $employee->uuid)
        );

        $response->assertOk()
            ->assertJsonPath('data.status', false);

        $this->assertFalse($employee->fresh()->status);
    }

    /** @test */
    public function admin_can_view_employee_roles(): void
    {
        $this->signInAdmin();

        Role::factory()->create([
            'name' => 'Manager',
        ]);

        $response = $this->getJson(route('admin.api.employees.roles.list'));

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Manager']);
    }

    /** @test */
    public function admin_can_view_employee_statistics(): void
    {
        $this->signInAdmin();

        $role = Role::factory()->create();

        Admin::factory()->create([
            'role_id' => $role->id,
            'status' => true,
        ]);

        Admin::factory()->create([
            'role_id' => $role->id,
            'status' => false,
        ]);

        $total = Admin::count();
        $active = Admin::where('status', true)->count();
        $inactive = Admin::where('status', false)->count();

        $response = $this->getJson(route('admin.api.employees.statistics'));

        $response->assertOk()
            ->assertJsonPath('data.total', $total)
            ->assertJsonPath('data.active', $active)
            ->assertJsonPath('data.inactive', $inactive);
    }
}


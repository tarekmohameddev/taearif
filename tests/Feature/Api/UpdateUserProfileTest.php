<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Api\FooterSetting;
use App\Models\User;
use App\Models\User\BasicSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UpdateUserProfileTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_basic_settings', 'api_footer_settings'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantPermissions(User $user, User $tenant, array $permissions): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $tenant->id);
        $registrar->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            try {
                $permission = Permission::findByName($permissionName, 'sanctum');
            } catch (\Throwable $e) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'sanctum',
                    'team_id' => $tenant->id,
                ]);
            }

            $user->givePermissionTo($permission);
        }

        $registrar->forgetCachedPermissions();
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->skipIfMissingSchema();

        $this->putJson('/api/user/profile', [
            'first_name' => 'Updated',
        ])->assertStatus(401);
    }

    public function test_tenant_can_update_personal_fields_and_receive_refreshed_profile(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'tenant-profile-' . uniqid('', true) . '@example.com',
            'phone' => '+966500000001',
            'password' => Hash::make('password123'),
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->putJson('/api/user/profile', [
            'first_name' => 'New',
            'last_name' => 'Tenant',
            'email' => $tenant->email,
            'phone' => '+966500000099',
            'address' => 'Personal Address',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Profile updated successfully')
            ->assertJsonPath('data.first_name', 'New')
            ->assertJsonPath('data.last_name', 'Tenant')
            ->assertJsonPath('data.phone', '+966500000099')
            ->assertJsonPath('data.address', 'Personal Address');

        $tenant->refresh();
        $this->assertSame('New', $tenant->first_name);
        $this->assertSame('+966500000099', $tenant->phone);
    }

    public function test_tenant_can_update_company_fields_without_overwriting_footer_sections(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create([
            'email' => 'tenant-company-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password123'),
        ]);

        BasicSetting::query()->create([
            'user_id' => $tenant->id,
            'company_name' => 'Old Company',
        ]);

        FooterSetting::query()->create([
            'user_id' => $tenant->id,
            'general' => [
                'companyName' => 'Old Company',
                'address' => 'Old Address',
            ],
            'social' => [
                ['id' => '1', 'platform' => 'facebook', 'url' => 'https://facebook.com/', 'enabled' => true],
            ],
            'columns' => [
                ['id' => '1', 'title' => 'Links', 'links' => [], 'enabled' => true],
            ],
            'newsletter' => ['enabled' => true, 'title' => 'Newsletter'],
            'style' => ['layout' => 'full-width'],
            'status' => true,
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->putJson('/api/user/profile', [
            'company_name' => 'New Company',
            'company_email' => 'company@example.com',
            'company_phone' => '+966511111111',
            'company_address' => 'Company Street',
            'working_hours' => 'Sun-Thu 9-5',
            'valLicense' => 'VAL-123',
            'district' => 'Al Olaya',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.company_name', 'New Company')
            ->assertJsonPath('data.valLicense', 'VAL-123');

        $basicSetting = BasicSetting::query()->where('user_id', $tenant->id)->first();
        $this->assertNotNull($basicSetting);
        $this->assertSame('New Company', $basicSetting->company_name);
        $this->assertSame('company@example.com', $basicSetting->email);

        $footer = FooterSetting::query()->where('user_id', $tenant->id)->first();
        $this->assertNotNull($footer);
        $this->assertSame('New Company', $footer->general['companyName']);
        $this->assertSame('company@example.com', $footer->general['email']);
        $this->assertSame('+966511111111', $footer->general['phone']);
        $this->assertSame('Company Street', $footer->general['address']);
        $this->assertSame('Sun-Thu 9-5', $footer->general['workingHours']);
        $this->assertSame('VAL-123', $footer->general['valLicense']);
        $this->assertCount(1, $footer->social);
        $this->assertCount(1, $footer->columns);
        $this->assertSame('Newsletter', $footer->newsletter['title']);

        $tenant->refresh();
        $this->assertSame('Al Olaya', $tenant->state);
    }

    public function test_email_and_phone_validation_reject_duplicates_but_allow_current_values(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create([
            'email' => 'tenant-unique-' . uniqid('', true) . '@example.com',
            'phone' => '+966500000010',
            'password' => Hash::make('password123'),
        ]);

        $other = User::factory()->tenant()->create([
            'email' => 'other-unique-' . uniqid('', true) . '@example.com',
            'phone' => '+966500000020',
            'password' => Hash::make('password123'),
        ]);

        Sanctum::actingAs($tenant);

        $this->putJson('/api/user/profile', [
            'email' => $tenant->email,
            'phone' => $tenant->phone,
        ])->assertOk();

        $this->putJson('/api/user/profile', [
            'email' => $other->email,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->putJson('/api/user/profile', [
            'phone' => $other->phone,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_password_update_requires_current_password_and_stores_new_hash(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create([
            'email' => 'tenant-password-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('old-password'),
        ]);

        Sanctum::actingAs($tenant);

        $this->putJson('/api/user/profile', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->putJson('/api/user/profile', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->putJson('/api/user/profile', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $tenant->refresh();
        $this->assertTrue(Hash::check('new-password', $tenant->password));
    }

    public function test_employee_without_settings_permission_cannot_update_company_fields(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create([
            'email' => 'tenant-owner-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password123'),
        ]);

        $employee = User::factory()->employee()->create([
            'tenant_id' => $tenant->id,
            'email' => 'employee-profile-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password123'),
        ]);

        BasicSetting::query()->create([
            'user_id' => $tenant->id,
            'company_name' => 'Tenant Company',
        ]);

        Sanctum::actingAs($employee);

        $this->putJson('/api/user/profile', [
            'first_name' => 'Employee',
        ])->assertOk()
            ->assertJsonPath('data.first_name', 'Employee');

        $this->putJson('/api/user/profile', [
            'company_name' => 'Hacked Company',
        ])->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');

        $this->putJson('/api/user/profile', [
            'valLicense' => 'VAL-HACKED',
        ])->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');

        $basicSetting = BasicSetting::query()->where('user_id', $tenant->id)->first();
        $this->assertSame('Tenant Company', $basicSetting?->company_name);
    }

    public function test_omitting_val_license_still_allows_profile_update(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create([
            'first_name' => 'Old',
            'email' => 'tenant-optional-val-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password123'),
        ]);

        BasicSetting::query()->create([
            'user_id' => $tenant->id,
            'company_name' => 'Optional Val Co',
        ]);

        FooterSetting::query()->create([
            'user_id' => $tenant->id,
            'general' => [
                'companyName' => 'Optional Val Co',
            ],
            'social' => [],
            'columns' => [],
            'newsletter' => [],
            'style' => [],
            'status' => true,
        ]);

        Sanctum::actingAs($tenant);

        $this->putJson('/api/user/profile', [
            'first_name' => 'Updated',
            'company_name' => 'Optional Val Co Updated',
        ])->assertOk()
            ->assertJsonPath('data.first_name', 'Updated')
            ->assertJsonPath('data.company_name', 'Optional Val Co Updated');

        $footer = FooterSetting::query()->where('user_id', $tenant->id)->first();
        $this->assertNotNull($footer);
        $this->assertArrayNotHasKey('valLicense', $footer->general ?? []);
    }
}

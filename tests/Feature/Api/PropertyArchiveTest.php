<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Property\PropertyDocument;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Services\Property\PropertyDocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertyArchiveTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresPropertyStatusColumns;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePropertyStatusColumns();

        foreach (['users', 'user_properties', 'property_documents'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    public function test_post_deed_with_image_and_deed_number(): void
    {
        Storage::fake('public');

        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $file = UploadedFile::fake()->image('deed-scan.jpg');

        $response = $this->post("/api/properties/{$property->id}/archive", [
            'type' => 'deed',
            'title' => 'صك الوحدة',
            'meta' => ['deed_number' => '1234567890'],
            'attachments' => [$file],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.type', 'deed')
            ->assertJsonPath('data.title', 'صك الوحدة')
            ->assertJsonPath('data.meta.deed_number', '1234567890')
            ->assertJsonPath('data.attachments.0.name', 'deed-scan.jpg')
            ->assertJsonPath('data.created_by.id', $tenant->id);

        $attachmentPath = $response->json('data.attachments.0.path');
        $this->assertNotEmpty($attachmentPath);
        Storage::disk('public')->assertExists($attachmentPath);

        $attachmentUrl = $response->json('data.attachments.0.url');
        $this->assertIsString($attachmentUrl);
        $this->assertStringContainsString($attachmentPath, $attachmentUrl);
    }

    public function test_post_water_meter(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $this->postJson("/api/properties/{$property->id}/archive", [
            'type' => 'meter',
            'title' => 'عداد المياه',
            'meta' => [
                'meter_kind' => 'water',
                'meter_number' => 'WM-9988',
                'reading' => '450',
                'reading_date' => '2026-06-01',
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'meter')
            ->assertJsonPath('data.meta.meter_kind', 'water')
            ->assertJsonPath('data.meta.meter_number', 'WM-9988')
            ->assertJsonPath('data.meta.reading', '450')
            ->assertJsonPath('data.meta.reading_date', '2026-06-01');
    }

    public function test_post_electricity_meter(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $this->postJson("/api/properties/{$property->id}/archive", [
            'type' => 'meter',
            'title' => 'عداد الكهرباء',
            'meta' => [
                'meter_kind' => 'electricity',
                'meter_number' => 'EM-5544',
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'meter')
            ->assertJsonPath('data.meta.meter_kind', 'electricity')
            ->assertJsonPath('data.meta.meter_number', 'EM-5544');
    }

    public function test_post_document_with_pdf(): void
    {
        Storage::fake('public');

        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $file = UploadedFile::fake()->create('lease.pdf', 50, 'application/pdf');

        $response = $this->post("/api/properties/{$property->id}/archive", [
            'type' => 'document',
            'title' => 'عقد إيجار',
            'attachments' => [$file],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'document')
            ->assertJsonPath('data.title', 'عقد إيجار')
            ->assertJsonPath('data.attachments.0.name', 'lease.pdf');

        $attachmentPath = $response->json('data.attachments.0.path');
        Storage::disk('public')->assertExists($attachmentPath);
    }

    public function test_get_lists_archive_items_newest_first(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $service = app(PropertyDocumentService::class);
        $service->storeArchiveItem($property, 'deed', 'First deed', null, [], ['deed_number' => '111'], $tenant->id);
        $service->storeArchiveItem($property, 'meter', 'Water meter', null, [], [
            'meter_kind' => 'water',
            'meter_number' => 'WM-1',
        ], $tenant->id);

        $this->getJson("/api/properties/{$property->id}/archive?per_page=10")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.type', 'meter')
            ->assertJsonPath('data.0.meta.meter_kind', 'water')
            ->assertJsonPath('data.1.type', 'deed')
            ->assertJsonPath('data.1.meta.deed_number', '111');
    }

    public function test_get_excludes_notes(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        PropertyDocument::create([
            'property_id' => $property->id,
            'type' => 'note',
            'content' => 'Internal note only',
            'created_by' => $tenant->id,
        ]);
        PropertyDocument::create([
            'property_id' => $property->id,
            'type' => 'deed',
            'title' => 'Deed copy',
            'meta' => ['deed_number' => 'DEED-99'],
            'created_by' => $tenant->id,
        ]);

        $this->getJson("/api/properties/{$property->id}/archive")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'deed')
            ->assertJsonPath('data.0.meta.deed_number', 'DEED-99');
    }

    public function test_get_filters_by_type(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $service = app(PropertyDocumentService::class);
        $service->storeArchiveItem($property, 'deed', 'Deed', null, [], ['deed_number' => 'D-1'], $tenant->id);
        $service->storeArchiveItem($property, 'meter', 'Meter', null, [], [
            'meter_kind' => 'water',
            'meter_number' => 'WM-1',
        ], $tenant->id);
        $service->storeArchiveItem($property, 'document', 'Contract', null, [], null, $tenant->id);

        $this->getJson("/api/properties/{$property->id}/archive?type=deed")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'deed');
    }

    public function test_post_forbidden_without_properties_update_permission(): void
    {
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        $this->postJson("/api/properties/{$property->id}/archive", [
            'type' => 'document',
            'title' => 'Blocked document',
        ])->assertForbidden();
    }

    public function test_get_forbidden_without_properties_view_permission(): void
    {
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        $this->getJson("/api/properties/{$property->id}/archive")
            ->assertForbidden();
    }

    public function test_employee_with_permissions_can_create(): void
    {
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'first_name' => 'Sara',
            'last_name' => 'Ahmed',
            'tenant_id' => $tenant->id,
        ]);

        $this->grantPermissions($employee, $tenant, ['properties.view', 'properties.update']);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        $this->postJson("/api/properties/{$property->id}/archive", [
            'type' => 'document',
            'title' => 'Employee document',
        ])
            ->assertCreated()
            ->assertJsonPath('data.created_by.id', $employee->id)
            ->assertJsonPath('data.created_by.name', 'Sara Ahmed');
    }

    public function test_post_returns_404_for_property_outside_tenant_scope(): void
    {
        $this->actingAsTenant();
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $property = $this->createProperty($otherTenant->id);

        $this->postJson("/api/properties/{$property->id}/archive", [
            'type' => 'document',
            'title' => 'Cross-tenant document',
        ])->assertNotFound();
    }

    public function test_get_returns_404_for_property_outside_tenant_scope(): void
    {
        $this->actingAsTenant();
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $property = $this->createProperty($otherTenant->id);

        $this->getJson("/api/properties/{$property->id}/archive")
            ->assertNotFound();
    }

    public function test_deed_without_any_data_returns_422(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $this->postJson("/api/properties/{$property->id}/archive", [
            'type' => 'deed',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_meter_without_meter_kind_returns_422(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $this->postJson("/api/properties/{$property->id}/archive", [
            'type' => 'meter',
            'meta' => ['meter_number' => 'WM-123'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['meta.meter_kind']);
    }

    private function actingAsTenant(): User
    {
        $user = User::factory()->create(['account_type' => 'tenant']);
        Sanctum::actingAs($user);

        return $user;
    }

    private function createProperty(int $userId): Property
    {
        $property = Property::create([
            'user_id' => $userId,
            'price' => 100000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'test.jpg',
            'property_type' => 'apartment',
        ]);

        PropertyContent::create([
            'user_id' => $userId,
            'property_id' => $property->id,
            'language_id' => 1,
            'title' => 'Test ' . $property->id,
            'slug' => 'test-' . $property->id,
            'address' => 'Address',
            'description' => 'Description',
        ]);

        return $property->fresh(['contents']);
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
}

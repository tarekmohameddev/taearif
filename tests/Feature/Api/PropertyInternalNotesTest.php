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

class PropertyInternalNotesTest extends TestCase
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

    public function test_post_creates_internal_note_with_created_by_and_type(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $response = $this->postJson("/api/properties/{$property->id}/internal-notes", [
            'note' => 'Buyer requested price negotiation.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.type', 'note')
            ->assertJsonPath('data.content', 'Buyer requested price negotiation.')
            ->assertJsonPath('data.created_by.id', $tenant->id);

        $expectedName = trim(($tenant->first_name ?? '') . ' ' . ($tenant->last_name ?? '')) ?: $tenant->username;
        $response->assertJsonPath('data.created_by.name', $expectedName);

        $this->assertNotNull($response->json('data.created_at'));

        $this->assertDatabaseHas('property_documents', [
            'property_id' => $property->id,
            'type' => 'note',
            'content' => 'Buyer requested price negotiation.',
            'created_by' => $tenant->id,
        ]);
    }

    public function test_post_with_attachments_stores_files_and_returns_urls(): void
    {
        Storage::fake('public');

        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $file = UploadedFile::fake()->create('offer.pdf', 20, 'application/pdf');

        $response = $this->post("/api/properties/{$property->id}/internal-notes", [
            'note' => 'Attached offer letter.',
            'attachments' => [$file],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.attachments.0.name', 'offer.pdf')
            ->assertJsonPath('data.attachments.0.size', $file->getSize());

        $attachmentPath = $response->json('data.attachments.0.path');
        $this->assertNotEmpty($attachmentPath);
        Storage::disk('public')->assertExists($attachmentPath);

        $attachmentUrl = $response->json('data.attachments.0.url');
        $this->assertIsString($attachmentUrl);
        $this->assertStringContainsString($attachmentPath, $attachmentUrl);
    }

    public function test_get_lists_internal_notes_for_property(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $service = app(PropertyDocumentService::class);
        $service->storeNote($property, 'First note', [], $tenant->id);
        $service->storeNote($property, 'Second note', [], $tenant->id);

        $this->getJson("/api/properties/{$property->id}/internal-notes?per_page=10")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('data.0.content', 'Second note')
            ->assertJsonPath('data.1.content', 'First note');
    }

    public function test_get_does_not_include_archive_items(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        PropertyDocument::create([
            'property_id' => $property->id,
            'type' => 'note',
            'content' => 'Visible note',
            'created_by' => $tenant->id,
        ]);
        PropertyDocument::create([
            'property_id' => $property->id,
            'type' => 'deed',
            'title' => 'Deed copy',
            'content' => 'Should not appear in notes list',
            'created_by' => $tenant->id,
        ]);

        $this->getJson("/api/properties/{$property->id}/internal-notes")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'note')
            ->assertJsonPath('data.0.content', 'Visible note');
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

        $this->postJson("/api/properties/{$property->id}/internal-notes", [
            'note' => 'Should be blocked',
        ])->assertForbidden();
    }

    public function test_employee_with_update_permission_can_create_note(): void
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

        $this->grantPermissions($employee, $tenant, ['properties.update']);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        $this->postJson("/api/properties/{$property->id}/internal-notes", [
            'note' => 'Employee internal note',
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

        $this->postJson("/api/properties/{$property->id}/internal-notes", [
            'note' => 'Cross-tenant note',
        ])->assertNotFound();
    }

    public function test_get_returns_404_for_property_outside_tenant_scope(): void
    {
        $this->actingAsTenant();
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $property = $this->createProperty($otherTenant->id);

        $this->getJson("/api/properties/{$property->id}/internal-notes")
            ->assertNotFound();
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

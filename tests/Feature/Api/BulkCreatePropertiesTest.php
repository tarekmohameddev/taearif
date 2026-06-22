<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\Property\BulkImportBatch;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class BulkCreatePropertiesTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresPropertyStatusColumns;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePropertyStatusColumns();
        config(['queue.default' => 'sync']);
    }

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'bulk_import_batches', 'user_properties', 'user_property_contents', 'memberships', 'packages', 'user_languages', 'api_permissions', 'api_model_has_permissions'] as $table) {
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
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

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

    private function seedTenantContext(User $tenant, int $propertyLimit = 100): Package
    {
        $package = Package::firstOrCreate(
            ['title' => 'Bulk Create Test Package'],
            [
                'slug' => 'bulk-create-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => $propertyLimit,
                'serial_number' => 997,
            ]
        );

        $package->update(['real_estate_limit_number' => $propertyLimit]);

        $membership = Membership::firstOrNew(['user_id' => $tenant->id]);
        $membership->status = 1;
        $membership->start_date = now()->subDay();
        $membership->expire_date = now()->addMonth();
        $membership->package_id = $package->id;
        $membership->price = 0;
        $membership->currency = 'USD';
        $membership->currency_symbol = '$';
        $membership->payment_method = 'test';
        $membership->transaction_id = 'bulk-create-' . uniqid();
        $membership->save();

        MembershipCacheService::clearCache($tenant->id);

        Language::firstOrCreate(
            ['user_id' => $tenant->id, 'is_default' => 1],
            [
                'name' => 'Arabic',
                'code' => 'ar',
                'rtl' => 1,
            ]
        );

        return $package;
    }

    private function actingAsTenantWithCreate(): User
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        return $tenant;
    }

    private function bulkPayload(array $units, array $overrides = []): array
    {
        return array_merge(['units' => $units], $overrides);
    }

    public function test_happy_path_creates_two_units_with_initial_report(): void
    {
        $this->skipIfMissingSchema();
        $tenant = $this->actingAsTenantWithCreate();

        $response = $this->postJson('/api/properties/bulk', $this->bulkPayload([
            ['title' => 'Unit A-101', 'price' => 450000, 'area' => 120],
            ['title' => 'Unit A-102', 'price' => 460000, 'area' => 125],
        ]));

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.valid', 2)
            ->assertJsonPath('data.invalid', 0)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonCount(2, 'data.rows');

        $batchId = $response->json('data.batch_id');
        $this->assertNotNull($batchId);

        $batch = BulkImportBatch::find($batchId);
        $this->assertNotNull($batch);
        $this->assertSame($tenant->id, (int) $batch->user_id);
        $this->assertSame('done', $batch->status);
        $this->assertSame(2, $batch->succeeded);

        $properties = Property::where('import_batch_id', (string) $batchId)->get();
        $this->assertCount(2, $properties);
        $this->assertTrue($properties->every(fn (Property $p) => (int) $p->user_id === $tenant->id));
    }

    public function test_batch_publish_status_draft_applies_to_all_units(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $response = $this->postJson('/api/properties/bulk', $this->bulkPayload(
            [
                ['title' => 'Draft Unit 1'],
                ['title' => 'Draft Unit 2'],
            ],
            ['publish_status' => 'draft'],
        ));

        $response->assertCreated()
            ->assertJsonPath('data.valid', 2);

        $batchId = $response->json('data.batch_id');
        $properties = Property::where('import_batch_id', (string) $batchId)->get();

        $this->assertCount(2, $properties);
        $this->assertTrue($properties->every(fn (Property $p) => $p->publish_status === 'draft' && (int) $p->status === 0));
    }

    public function test_per_unit_publish_status_overrides_batch_default(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $response = $this->postJson('/api/properties/bulk', $this->bulkPayload(
            [
                ['title' => 'Inherited Draft'],
                ['title' => 'Explicit Published', 'publish_status' => 'published'],
            ],
            ['publish_status' => 'draft'],
        ));

        $response->assertCreated();

        $batchId = $response->json('data.batch_id');
        $properties = Property::where('import_batch_id', (string) $batchId)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $properties);
        $this->assertSame('draft', $properties[0]->publish_status);
        $this->assertSame('published', $properties[1]->publish_status);
    }

    public function test_invalid_status_combo_marks_row_invalid_and_applies_valid_rows_only(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $response = $this->postJson('/api/properties/bulk', $this->bulkPayload([
            ['title' => 'Valid Unit'],
            ['title' => 'Invalid Combo', 'listing_purpose' => 'sale', 'unit_status' => 'rented'],
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.valid', 1)
            ->assertJsonPath('data.invalid', 1)
            ->assertJsonPath('data.rows.1.valid', false);

        $batchId = $response->json('data.batch_id');
        $batch = BulkImportBatch::find($batchId);

        $this->assertSame(1, $batch->succeeded);
        $this->assertSame(0, $batch->failed);
        $this->assertCount(1, Property::where('import_batch_id', (string) $batchId)->get());
    }

    public function test_all_invalid_rows_return_422_without_batch(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $beforeCount = BulkImportBatch::count();

        $response = $this->postJson('/api/properties/bulk', $this->bulkPayload([
            ['title' => 'Bad 1', 'listing_purpose' => 'sale', 'unit_status' => 'rented'],
            ['title' => 'Bad 2', 'listing_purpose' => 'rent', 'unit_status' => 'sold'],
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('data.valid', 0)
            ->assertJsonPath('data.invalid', 2)
            ->assertJsonMissingPath('data.batch_id');

        $this->assertSame($beforeCount, BulkImportBatch::count());
    }

    public function test_auto_apply_false_keeps_batch_pending_without_properties(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $response = $this->postJson('/api/properties/bulk', $this->bulkPayload(
            [['title' => 'Preview Only Unit']],
            ['auto_apply' => false],
        ));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.valid', 1);

        $batchId = $response->json('data.batch_id');
        $batch = BulkImportBatch::find($batchId);

        $this->assertSame('pending', $batch->status);
        $this->assertCount(0, Property::where('import_batch_id', (string) $batchId)->get());
    }

    public function test_apply_endpoint_after_preview_creates_properties(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $createResponse = $this->postJson('/api/properties/bulk', $this->bulkPayload(
            [['title' => 'Deferred Unit']],
            ['auto_apply' => false],
        ));

        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        $applyResponse = $this->postJson("/api/properties/import/{$batchId}/apply");
        $applyResponse->assertOk()
            ->assertJsonPath('data.status', 'processing');

        $batch = BulkImportBatch::find($batchId);
        $this->assertSame('done', $batch->status);
        $this->assertCount(1, Property::where('import_batch_id', (string) $batchId)->get());
    }

    public function test_membership_limit_exceeded_returns_403_before_apply(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant, 1);
        $this->grantPermissions($tenant, $tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        Property::create([
            'user_id' => $tenant->id,
            'featured_image' => 'x.jpg',
            'purpose' => 'sale',
            'completion_status' => 'complete',
            'status' => 1,
        ]);

        $response = $this->postJson('/api/properties/bulk', $this->bulkPayload([
            ['title' => 'Over Limit Unit'],
        ]));

        $response->assertForbidden()
            ->assertJsonPath('message', 'You have reached your property listing limit.');
    }

    public function test_employee_created_batch_is_readable_by_tenant_owner(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->seedTenantContext($tenant);
        $this->grantPermissions($employee, $tenant, ['properties.create']);

        Sanctum::actingAs($employee);
        $createResponse = $this->postJson('/api/properties/bulk', $this->bulkPayload([
            ['title' => 'Employee Created Unit'],
        ]));
        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        $batch = BulkImportBatch::find($batchId);
        $this->assertSame($tenant->id, (int) $batch->user_id);

        Sanctum::actingAs($tenant);
        $this->getJson("/api/properties/import/{$batchId}/report")
            ->assertOk()
            ->assertJsonPath('data.batch_id', $batchId);
    }

    public function test_batch_project_id_is_inherited_by_created_properties(): void
    {
        $this->skipIfMissingSchema();

        if (! Schema::hasTable('user_projects')) {
            $this->markTestSkipped('user_projects table not available.');
        }

        $tenant = $this->actingAsTenantWithCreate();
        $project = Project::create([
            'user_id' => $tenant->id,
            'featured_image' => 'projects/test.jpg',
            'min_price' => 100000,
            'max_price' => 200000,
            'featured' => 0,
            'published' => 1,
            'developer' => 'Test Developer',
            'units' => 10,
            'completion_date' => now()->addYear()->toDateString(),
            'complete_status' => 0,
        ]);

        $response = $this->postJson('/api/properties/bulk', $this->bulkPayload(
            [
                ['title' => 'Project Unit 1'],
                ['title' => 'Project Unit 2'],
            ],
            ['project_id' => $project->id],
        ));

        $response->assertCreated();

        $batchId = $response->json('data.batch_id');
        $properties = Property::where('import_batch_id', (string) $batchId)->get();

        $this->assertCount(2, $properties);
        $this->assertTrue($properties->every(fn (Property $p) => (int) $p->project_id === $project->id));
    }
}

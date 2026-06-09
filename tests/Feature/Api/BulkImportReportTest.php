<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\Property\BulkImportBatch;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Services\MembershipCacheService;
use App\Services\Property\BulkPropertyImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class BulkImportReportTest extends TestCase
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

    private function seedTenantContext(User $tenant, int $propertyLimit = 100): void
    {
        $package = Package::firstOrCreate(
            ['title' => 'Bulk Report Test Package'],
            [
                'slug' => 'bulk-report-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => $propertyLimit,
                'serial_number' => 996,
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
        $membership->transaction_id = 'bulk-report-' . uniqid();
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
    }

    private function actingAsTenantWithPermissions(array $permissions): User
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, $permissions);
        Sanctum::actingAs($tenant);

        return $tenant;
    }

    private function bulkPayload(array $units, array $overrides = []): array
    {
        return array_merge(['units' => $units], $overrides);
    }

    public function test_report_after_auto_apply_bulk_create_returns_done_with_property_ids(): void
    {
        $this->skipIfMissingSchema();
        $tenant = $this->actingAsTenantWithPermissions(['properties.create', 'properties.view']);

        $createResponse = $this->postJson('/api/properties/bulk', $this->bulkPayload([
            ['title' => 'Unit A-101', 'price' => 450000],
            ['title' => 'Unit A-102', 'price' => 460000],
        ]));

        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        $response = $this->getJson("/api/properties/import/{$batchId}/report");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.batch_id', $batchId)
            ->assertJsonPath('data.source', 'table')
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.succeeded', 2)
            ->assertJsonPath('data.failed', 0)
            ->assertJsonPath('data.skipped', 0)
            ->assertJsonCount(2, 'data.rows');

        $rows = $response->json('data.rows');
        $this->assertSame('succeeded', $rows[0]['status']);
        $this->assertSame('succeeded', $rows[1]['status']);
        $this->assertSame('Unit A-101', $rows[0]['title']);
        $this->assertSame('Unit A-102', $rows[1]['title']);
        $this->assertNotNull($rows[0]['property_id']);
        $this->assertNotNull($rows[1]['property_id']);

        $properties = Property::where('import_batch_id', (string) $batchId)->get();
        $this->assertCount(2, $properties);
        $this->assertTrue($properties->every(fn (Property $p) => (int) $p->user_id === $tenant->id));
    }

    public function test_report_marks_invalid_preview_row_as_skipped_and_valid_as_succeeded(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithPermissions(['properties.create', 'properties.view']);

        $createResponse = $this->postJson('/api/properties/bulk', $this->bulkPayload([
            ['title' => 'Valid Unit'],
            ['title' => 'Invalid Combo', 'listing_purpose' => 'sale', 'unit_status' => 'rented'],
        ]));

        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        $response = $this->getJson("/api/properties/import/{$batchId}/report");

        $response->assertOk()
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.succeeded', 1)
            ->assertJsonPath('data.failed', 0)
            ->assertJsonPath('data.skipped', 1)
            ->assertJsonPath('data.rows.0.status', 'succeeded')
            ->assertJsonPath('data.rows.0.title', 'Valid Unit')
            ->assertJsonPath('data.rows.1.status', 'skipped')
            ->assertJsonPath('data.rows.1.title', 'Invalid Combo');

        $skippedErrors = $response->json('data.rows.1.errors');
        $this->assertNotEmpty($skippedErrors);
    }

    public function test_report_before_apply_shows_pending_valid_rows_and_skipped_invalid(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithPermissions(['properties.create', 'properties.view']);

        $createResponse = $this->postJson('/api/properties/bulk', $this->bulkPayload(
            [
                ['title' => 'Pending Unit'],
                ['title' => 'Bad Combo', 'listing_purpose' => 'sale', 'unit_status' => 'rented'],
            ],
            ['auto_apply' => false],
        ));

        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        $response = $this->getJson("/api/properties/import/{$batchId}/report");

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.succeeded', 0)
            ->assertJsonPath('data.failed', 0)
            ->assertJsonPath('data.skipped', 1)
            ->assertJsonPath('data.rows.0.status', 'pending')
            ->assertJsonPath('data.rows.0.title', 'Pending Unit')
            ->assertJsonPath('data.rows.1.status', 'skipped');
    }

    public function test_report_after_manual_apply_returns_done(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithPermissions(['properties.create', 'properties.view']);

        $createResponse = $this->postJson('/api/properties/bulk', $this->bulkPayload(
            [['title' => 'Deferred Unit']],
            ['auto_apply' => false],
        ));

        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        $this->postJson("/api/properties/import/{$batchId}/apply")->assertOk();

        $response = $this->getJson("/api/properties/import/{$batchId}/report");

        $response->assertOk()
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.succeeded', 1)
            ->assertJsonPath('data.rows.0.status', 'succeeded');

        $this->assertNotNull($response->json('data.rows.0.property_id'));
    }

    public function test_report_while_processing_shows_pending_rows(): void
    {
        $this->skipIfMissingSchema();
        $tenant = $this->actingAsTenantWithPermissions(['properties.create', 'properties.view']);

        $service = app(BulkPropertyImportService::class);
        $batch = $service->createTableBatch(
            $tenant->id,
            [
                ['title' => 'Unit One'],
                ['title' => 'Unit Two'],
            ],
            null,
            null,
            'draft',
        );

        $batch->update(['status' => 'processing']);
        Queue::fake();
        $service->processChunk($batch->fresh(), 0, 1);

        $this->grantPermissions($tenant, $tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $response = $this->getJson("/api/properties/import/{$batch->id}/report");

        $response->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.succeeded', 1)
            ->assertJsonPath('data.rows.0.status', 'succeeded')
            ->assertJsonPath('data.rows.1.status', 'pending');
    }

    public function test_employee_created_batch_report_readable_by_tenant_owner(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->seedTenantContext($tenant);
        $this->grantPermissions($employee, $tenant, ['properties.create']);
        $this->grantPermissions($tenant, $tenant, ['properties.view']);

        Sanctum::actingAs($employee);
        $createResponse = $this->postJson('/api/properties/bulk', $this->bulkPayload([
            ['title' => 'Employee Created Unit'],
        ]));
        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        Sanctum::actingAs($tenant);
        $response = $this->getJson("/api/properties/import/{$batchId}/report");

        $response->assertOk()
            ->assertJsonPath('data.batch_id', $batchId)
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.rows.0.status', 'succeeded')
            ->assertJsonPath('data.rows.0.title', 'Employee Created Unit');
    }

    public function test_other_tenant_batch_returns_404(): void
    {
        $this->skipIfMissingSchema();

        $tenantA = User::factory()->create(['account_type' => 'tenant']);
        $tenantB = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenantA);
        $this->seedTenantContext($tenantB);
        $this->grantPermissions($tenantA, $tenantA, ['properties.create', 'properties.view']);
        $this->grantPermissions($tenantB, $tenantB, ['properties.view']);

        Sanctum::actingAs($tenantA);
        $createResponse = $this->postJson('/api/properties/bulk', $this->bulkPayload([
            ['title' => 'Tenant A Unit'],
        ]));
        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        Sanctum::actingAs($tenantB);
        $this->getJson("/api/properties/import/{$batchId}/report")
            ->assertNotFound();
    }

    public function test_report_without_properties_view_returns_403(): void
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
            ['title' => 'No View Permission Unit'],
        ]));
        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        $this->getJson("/api/properties/import/{$batchId}/report")
            ->assertForbidden();
    }

    public function test_excel_batch_source_appears_in_report(): void
    {
        $this->skipIfMissingSchema();
        $tenant = $this->actingAsTenantWithPermissions(['properties.create', 'properties.view']);

        $batch = BulkImportBatch::create([
            'user_id' => $tenant->id,
            'source' => 'excel',
            'status' => 'pending',
            'publish_status' => 'draft',
            'total' => 1,
            'preview_data' => [
                [
                    'row' => 2,
                    'data' => ['title' => 'Excel Unit'],
                    'valid' => true,
                    'errors' => [],
                ],
            ],
            'report' => ['rows' => []],
        ]);

        $this->postJson("/api/properties/import/{$batch->id}/apply")->assertOk();

        $response = $this->getJson("/api/properties/import/{$batch->id}/report");

        $response->assertOk()
            ->assertJsonPath('data.source', 'excel')
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.rows.0.title', 'Excel Unit')
            ->assertJsonPath('data.rows.0.status', 'succeeded');
    }
}

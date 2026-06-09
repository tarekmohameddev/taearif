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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class BulkImportExcelTest extends TestCase
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
            ['title' => 'Bulk Excel Test Package'],
            [
                'slug' => 'bulk-excel-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => $propertyLimit,
                'serial_number' => 995,
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
        $membership->transaction_id = 'bulk-excel-' . uniqid();
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

    private function actingAsTenantWithCreate(): User
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        return $tenant;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     */
    private function makeExcelUpload(array $headers, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        foreach ($rows as $rIndex => $row) {
            foreach ($row as $cIndex => $value) {
                $sheet->setCellValueByColumnAndRow($cIndex + 1, $rIndex + 2, $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'units.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function postExcelImport(UploadedFile $file, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/api/properties/import/excel', array_merge(['file' => $file], $extra), [
            'Accept' => 'application/json',
        ]);
    }

    public function test_happy_path_english_headers_creates_units_with_initial_report(): void
    {
        $this->skipIfMissingSchema();
        $tenant = $this->actingAsTenantWithCreate();

        $file = $this->makeExcelUpload(
            ['title', 'price', 'area'],
            [
                ['Unit A-101', 450000, 120],
                ['Unit A-102', 460000, 125],
            ],
        );

        $response = $this->postExcelImport($file);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.valid', 2)
            ->assertJsonPath('data.invalid', 0)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonCount(2, 'data.rows');

        $batchId = $response->json('data.batch_id');
        $batch = BulkImportBatch::find($batchId);

        $this->assertNotNull($batch);
        $this->assertSame($tenant->id, (int) $batch->user_id);
        $this->assertSame('excel', $batch->source);
        $this->assertSame('done', $batch->status);
        $this->assertSame(2, $batch->succeeded);

        $properties = Property::where('import_batch_id', (string) $batchId)->get();
        $this->assertCount(2, $properties);
    }

    public function test_arabic_template_headers_parse_valid_row(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $file = $this->makeExcelUpload(
            ['عنوان الإعلان', 'السعر', 'الغرض'],
            [
                ['وحدة عربية', 500000, 'بيع'],
            ],
        );

        $response = $this->postExcelImport($file);

        $response->assertCreated()
            ->assertJsonPath('data.valid', 1)
            ->assertJsonPath('data.rows.0.row', 2);

        $batchId = $response->json('data.batch_id');
        $this->assertCount(1, Property::where('import_batch_id', (string) $batchId)->get());
    }

    public function test_partial_invalid_row_applies_valid_rows_only(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $file = $this->makeExcelUpload(
            ['title', 'listing_purpose', 'unit_status'],
            [
                ['Valid Unit', 'sale', 'available'],
                ['', 'sale', 'rented'],
            ],
        );

        $response = $this->postExcelImport($file);

        $response->assertCreated()
            ->assertJsonPath('data.valid', 1)
            ->assertJsonPath('data.invalid', 1)
            ->assertJsonPath('data.rows.1.valid', false);

        $batchId = $response->json('data.batch_id');
        $batch = BulkImportBatch::find($batchId);

        $this->assertSame(1, $batch->succeeded);
        $this->assertCount(1, Property::where('import_batch_id', (string) $batchId)->get());
    }

    public function test_all_invalid_rows_return_422_without_batch(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $beforeCount = BulkImportBatch::count();

        $file = $this->makeExcelUpload(
            ['title', 'listing_purpose', 'unit_status'],
            [
                ['Bad 1', 'sale', 'rented'],
                ['Bad 2', 'rent', 'sold'],
            ],
        );

        $response = $this->postExcelImport($file);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('data.valid', 0)
            ->assertJsonPath('data.invalid', 2)
            ->assertJsonMissingPath('data.batch_id');

        $this->assertSame($beforeCount, BulkImportBatch::count());
    }

    public function test_empty_file_header_only_returns_422(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $file = $this->makeExcelUpload(['title', 'price'], []);

        $response = $this->postExcelImport($file);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'No data rows found in file.')
            ->assertJsonPath('data.total', 0);
    }

    public function test_auto_apply_false_keeps_batch_pending_without_properties(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $file = $this->makeExcelUpload(['title'], [['Preview Only Unit']]);

        $response = $this->postExcelImport($file, ['auto_apply' => false]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.valid', 1);

        $batchId = $response->json('data.batch_id');
        $batch = BulkImportBatch::find($batchId);

        $this->assertSame('pending', $batch->status);
        $this->assertCount(0, Property::where('import_batch_id', (string) $batchId)->get());
    }

    public function test_manual_apply_after_preview_creates_properties(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $file = $this->makeExcelUpload(['title'], [['Deferred Excel Unit']]);

        $createResponse = $this->postExcelImport($file, ['auto_apply' => false]);
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

        $file = $this->makeExcelUpload(['title'], [['Over Limit Unit']]);

        $response = $this->postExcelImport($file);

        $response->assertForbidden()
            ->assertJsonPath('message', 'You have reached your property listing limit.');
    }

    public function test_employee_upload_batch_readable_by_tenant_owner(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->seedTenantContext($tenant);
        $this->grantPermissions($employee, $tenant, ['properties.create', 'properties.view']);

        Sanctum::actingAs($employee);
        $file = $this->makeExcelUpload(['title'], [['Employee Excel Unit']]);
        $createResponse = $this->postExcelImport($file);
        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        $batch = BulkImportBatch::find($batchId);
        $this->assertSame($tenant->id, (int) $batch->user_id);

        Sanctum::actingAs($tenant);
        $this->getJson("/api/properties/import/{$batchId}/report")
            ->assertOk()
            ->assertJsonPath('data.batch_id', $batchId)
            ->assertJsonPath('data.source', 'excel');
    }

    public function test_other_tenant_batch_returns_404_on_report(): void
    {
        $this->skipIfMissingSchema();

        $tenantA = $this->actingAsTenantWithCreate();
        $file = $this->makeExcelUpload(['title'], [['Tenant A Unit']]);
        $createResponse = $this->postExcelImport($file);
        $batchId = $createResponse->json('data.batch_id');

        $tenantB = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenantB);
        $this->grantPermissions($tenantB, $tenantB, ['properties.view']);
        Sanctum::actingAs($tenantB);

        $this->getJson("/api/properties/import/{$batchId}/report")->assertNotFound();

        $this->assertSame($tenantA->id, (int) BulkImportBatch::find($batchId)->user_id);
    }

    public function test_without_properties_create_permission_returns_403(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->seedTenantContext($tenant);
        Sanctum::actingAs($employee);

        $file = $this->makeExcelUpload(['title'], [['No Permission Unit']]);

        $this->postExcelImport($file)->assertForbidden();
    }

    public function test_row_limit_over_500_returns_422(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $rows = [];
        for ($i = 1; $i <= 501; $i++) {
            $rows[] = ["Unit {$i}"];
        }

        $file = $this->makeExcelUpload(['title'], $rows);

        $response = $this->postExcelImport($file);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_final_report_after_excel_upload_shows_succeeded_counts(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.create', 'properties.view']);
        Sanctum::actingAs($tenant);

        $file = $this->makeExcelUpload(
            ['title', 'listing_purpose', 'unit_status'],
            [
                ['Excel Good', 'sale', 'available'],
                ['', 'sale', 'available'],
            ],
        );

        $createResponse = $this->postExcelImport($file);
        $createResponse->assertCreated();
        $batchId = $createResponse->json('data.batch_id');

        $response = $this->getJson("/api/properties/import/{$batchId}/report");

        $response->assertOk()
            ->assertJsonPath('data.source', 'excel')
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.succeeded', 1)
            ->assertJsonPath('data.skipped', 1)
            ->assertJsonPath('data.rows.0.status', 'succeeded')
            ->assertJsonPath('data.rows.1.status', 'skipped');
    }
}

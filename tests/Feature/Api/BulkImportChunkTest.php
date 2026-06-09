<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Jobs\ProcessBulkPropertyImport;
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

class BulkImportChunkTest extends TestCase
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

    private function seedTenantContext(User $tenant): void
    {
        $package = Package::firstOrCreate(
            ['title' => 'Bulk Chunk Test Package'],
            [
                'slug' => 'bulk-chunk-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => 100,
                'serial_number' => 994,
            ]
        );

        $membership = Membership::firstOrNew(['user_id' => $tenant->id]);
        $membership->status = 1;
        $membership->start_date = now()->subDay();
        $membership->expire_date = now()->addMonth();
        $membership->package_id = $package->id;
        $membership->price = 0;
        $membership->currency = 'USD';
        $membership->currency_symbol = '$';
        $membership->payment_method = 'test';
        $membership->transaction_id = 'bulk-chunk-' . uniqid();
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
        $this->grantPermissions($tenant, $tenant, ['properties.create', 'properties.view']);
        Sanctum::actingAs($tenant);

        return $tenant;
    }

    /**
     * @return list<array{title: string}>
     */
    private function unitTitles(int $count): array
    {
        $units = [];
        for ($i = 1; $i <= $count; $i++) {
            $units[] = ['title' => "Chunk Unit {$i}"];
        }

        return $units;
    }

    public function test_apply_batch_processes_all_rows_across_multiple_chunks(): void
    {
        $this->skipIfMissingSchema();
        config(['bulk_import.chunk_size' => 3]);

        $this->actingAsTenantWithCreate();

        $response = $this->postJson('/api/properties/bulk', [
            'units' => $this->unitTitles(10),
        ]);

        $response->assertCreated();
        $batchId = $response->json('data.batch_id');
        $batch = BulkImportBatch::find($batchId);

        $this->assertNotNull($batch);
        $this->assertSame('done', $batch->status);
        $this->assertSame(10, $batch->succeeded);
        $this->assertSame(0, $batch->failed);
        $this->assertSame(10, (int) ($batch->report['meta']['processed_offset'] ?? 0));
        $this->assertSame(3, (int) ($batch->report['meta']['chunk_size'] ?? 0));
        $this->assertCount(10, Property::where('import_batch_id', (string) $batchId)->get());
    }

    public function test_process_chunk_skips_invalid_preview_rows_in_apply_report(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);

        $service = app(BulkPropertyImportService::class);
        $batch = $service->createTableBatch(
            $tenant->id,
            [
                ['title' => 'Valid Unit'],
                ['title' => 'Invalid Combo', 'listing_purpose' => 'sale', 'unit_status' => 'rented'],
            ],
            null,
            null,
            'draft',
        );

        $service->processChunk($batch->fresh(), 0, 50);
        $batch = $batch->fresh();

        $this->assertSame(1, $batch->succeeded);
        $this->assertCount(1, $batch->report['rows'] ?? []);
        $this->assertSame('succeeded', $batch->report['rows'][0]['status']);
    }

    public function test_report_while_chunked_processing_shows_pending_rows(): void
    {
        $this->skipIfMissingSchema();
        config(['bulk_import.chunk_size' => 1]);

        $tenant = $this->actingAsTenantWithCreate();
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

        $response = $this->getJson("/api/properties/import/{$batch->id}/report");

        $response->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.succeeded', 1)
            ->assertJsonPath('data.rows.0.status', 'succeeded')
            ->assertJsonPath('data.rows.1.status', 'pending');
    }

    public function test_job_failed_marks_batch_as_failed_with_reason(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);

        $service = app(BulkPropertyImportService::class);
        $batch = $service->createTableBatch(
            $tenant->id,
            [['title' => 'Failure Unit']],
            null,
            null,
            'draft',
        );
        $batch->update(['status' => 'processing']);

        $job = new ProcessBulkPropertyImport($batch->id, 0, 50);
        $job->failed(new \RuntimeException('Queue worker died'));

        $batch = $batch->fresh();
        $this->assertSame('failed', $batch->status);
        $this->assertSame('Queue worker died', $batch->report['meta']['failure_reason'] ?? null);
    }

    public function test_final_report_returns_failed_status_for_failed_batch(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->actingAsTenantWithCreate();
        $service = app(BulkPropertyImportService::class);
        $batch = $service->createTableBatch(
            $tenant->id,
            [['title' => 'Stalled Unit']],
            null,
            null,
            'draft',
        );

        $service->markBatchFailed($batch, 'Worker timeout');

        $response = $this->getJson("/api/properties/import/{$batch->id}/report");

        $response->assertOk()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.rows.0.status', 'pending');
    }

    public function test_resolve_chunk_size_reads_config(): void
    {
        config(['bulk_import.chunk_size' => 25]);

        $this->assertSame(25, app(BulkPropertyImportService::class)->resolveChunkSize());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\User\RealestateManagement\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PropertyRequestProjectMigrationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_backfills_legacy_project_id_into_the_project_pivot_idempotently(): void
    {
        foreach (['users_property_requests', 'user_projects', 'property_request_project'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing required table: {$table}.");
            }
        }

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $project = Project::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'projects/backfill-test.jpg',
            'min_price' => 100000,
            'max_price' => 200000,
            'featured' => 0,
            'published' => 1,
            'developer' => 'Backfill Test Developer',
            'units' => 1,
            'completion_date' => now()->addYear()->toDateString(),
            'complete_status' => 0,
        ]);
        $propertyRequestId = (int) DB::table('users_property_requests')->insertGetId([
            'user_id' => $tenant->id,
            'full_name' => 'Legacy Project Request',
            'phone' => '+966500009999',
            'project_id' => $project->id,
            'is_active' => 1,
            'is_read' => 0,
            'source' => 'import',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('property_request_project')
            ->where('property_request_id', $propertyRequestId)
            ->delete();

        $migration = require base_path('database/migrations/2026_08_16_000001_create_property_request_project_table.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, DB::table('property_request_project')
            ->where('property_request_id', $propertyRequestId)
            ->where('project_id', $project->id)
            ->count());
    }
}

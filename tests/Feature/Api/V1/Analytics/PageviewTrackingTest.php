<?php

namespace Tests\Feature\Api\V1\Analytics;

use App\Services\Analytics\PageviewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PageviewTrackingTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.pageview_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('pageview_testing');
        DB::setDefaultConnection('pageview_testing');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
        });
        Schema::create('user_property_contents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('slug');
        });
        Schema::create('user_project_contents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('slug');
        });
        Schema::create('pageview_analytics', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('page_slug')->default('');
            $table->string('dynamic_slug')->nullable();
            $table->string('full_path')->nullable();
            $table->string('page_path');
            $table->string('page_type')->nullable();
            $table->unsignedBigInteger('views_count')->default(1);
            $table->unsignedBigInteger('sessions_count')->default(0);
            $table->unsignedBigInteger('users_count')->default(0);
            $table->date('date_bucket');
            $table->timestamps();
            $table->unique(['tenant_id', 'page_path', 'date_bucket'], 'unique_tenant_path_date');
            $table->unique(
                ['tenant_id', 'page_slug', 'dynamic_slug', 'date_bucket'],
                'unique_tenant_page_date'
            );
        });

        $migration = require database_path(
            'migrations/2026_09_22_000001_replace_legacy_pageview_unique_constraint.php'
        );
        $migration->up();

        DB::table('users')->insert(['id' => 1, 'username' => 'tenant-owner']);
        DB::table('user_project_contents')->insert([
            'user_id' => 1,
            'slug' => 'localized-project',
        ]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('pageview_testing');
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('pageview_testing');

        parent::tearDown();
    }

    public function test_compatibility_payload_tracks_separate_localized_paths(): void
    {
        $service = app(PageviewService::class);

        $this->assertSame(1, $service->trackPageView(
            'tenant-owner',
            'project',
            'localized-project',
            '/ar/project/localized-project',
            'project',
            'Mozilla/5.0'
        ));
        $this->assertSame(1, $service->trackPageView(
            'tenant-owner',
            'project',
            'localized-project',
            '/en/project/localized-project',
            'project',
            'Mozilla/5.0'
        ));

        $this->assertSame(2, DB::table('pageview_analytics')->count());
        $this->assertSame(
            ['localized-project', 'localized-project'],
            DB::table('pageview_analytics')->orderBy('page_path')->pluck('page_slug')->all()
        );
    }

    public function test_normalized_path_is_incremented_atomically(): void
    {
        $service = app(PageviewService::class);

        $this->assertSame(1, $service->trackPageView(
            'tenant-owner',
            'localized-project',
            null,
            '/ar//project/localized-project/?source=test',
            'project',
            'Mozilla/5.0'
        ));
        $this->assertSame(2, $service->trackPageView(
            'tenant-owner',
            'localized-project',
            null,
            '/ar/project/localized-project',
            'project',
            'Mozilla/5.0'
        ));

        $this->assertDatabaseHas('pageview_analytics', [
            'page_path' => '/ar/project/localized-project',
            'views_count' => 2,
        ], 'pageview_testing');
    }

    public function test_invalid_internal_path_returns_validation_error(): void
    {
        $this->postJson('/api/v1/analytics/page-view', [
            'tenant_id' => 'tenant-owner',
            'slug' => 'localized-project',
            'dynamic_slug' => null,
            'path' => "/ar/project/localized-project\0",
            'page_type' => 'project',
        ])->assertStatus(422)->assertJsonValidationErrors('path');
    }

    public function test_unknown_content_returns_validation_error_even_for_a_bot(): void
    {
        $this->withHeader('User-Agent', 'Googlebot')->postJson('/api/v1/analytics/page-view', [
            'tenant_id' => 'tenant-owner',
            'slug' => 'unknown-project',
            'dynamic_slug' => null,
            'path' => '/ar/project/unknown-project',
            'page_type' => 'project',
        ])->assertStatus(422)->assertJsonValidationErrors('slug');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AlibabaOssService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\SkippedTest;
use Tests\TestCase;

/**
 * POST /api/v1/tenant-website/{tenantId}/job-applications
 *
 * Plan (coverage map):
 * - Validation: 422 + API error envelope (missing/invalid fields, non-PDF, oversized file).
 * - Tenant resolution: 404 when username/domain does not match a tenant ({@see \App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant}).
 * - Success: 201 + job_applications row; requires OSS upload ({@see AlibabaOssService::uploadFile}) — mock in tests.
 * - OSS failure: 500 + success false (controller catch block).
 * - Rate limit: throttle:api (60/min) + throttle:api_tenant_job_applications (100/min per IP) — only enforced when
 *   {@see \App\Providers\RouteServiceProvider::registerProductionAwareRateLimiters} applies (APP_ENV=production).
 *   Prefer a dedicated test with mocked limiter or targeted env override rather than running the full suite as production.
 *
 * Database: {@see DatabaseTransactions} — requires `taearif_testing` with core tables from your real schema (this project
 * does not ship a full migrate:fresh baseline for `users` and many legacy tables). Clone/import your dev DB or run
 * `php artisan migrate` against a DB that already has the base schema, then point `phpunit.xml` at it.
 *
 * @group tenant-website
 * @group job-applications
 */
class TenantWebsiteJobApplicationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Handler-only test: no DB / tenant fixtures required.
        if ($this->getName() === 'test_throttle_exception_renders_429_with_rate_limited_code') {
            return;
        }

        try {
            if (! Schema::hasTable('users') || ! Schema::hasTable('job_applications')) {
                $this->markTestSkipped(
                    'taearif_testing needs `users` and `job_applications` tables. Import/copy your application schema into that database (migrate:fresh alone is not sufficient for this codebase).'
                );
            }
        } catch (SkippedTest $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database unavailable: ' . $e->getMessage());
        }
    }

    private function endpoint(string $tenantId): string
    {
        return '/api/v1/tenant-website/' . $tenantId . '/job-applications';
    }

    private function validPdf(): UploadedFile
    {
        return UploadedFile::fake()->create('cv.pdf', 120, 'application/pdf');
    }

    public function test_validation_errors_return_422(): void
    {
        $response = $this->postJson($this->endpoint('any-tenant'), []);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_non_pdf_file_rejected_with_422(): void
    {
        $file = UploadedFile::fake()->create('resume.txt', 100, 'text/plain');

        $response = $this->postJson($this->endpoint('any-tenant'), [
            'name' => 'Jane Doe',
            'phone' => '+966500000001',
            'email' => 'jane@example.com',
            'pdf' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_unknown_tenant_returns_404(): void
    {
        $response = $this->postJson($this->endpoint('nonexistent-tenant-xyz'), [
            'name' => 'Jane Doe',
            'phone' => '+966500000001',
            'email' => 'jane@example.com',
            'pdf' => $this->validPdf(),
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', 'RESOURCE_NOT_FOUND');
    }

    public function test_successful_application_returns_201_and_persists_row(): void
    {
        $tenant = User::factory()->create([
            'username' => 'hiring-tenant',
        ]);

        $this->mock(AlibabaOssService::class, function ($mock) {
            $mock->shouldReceive('uploadFile')
                ->once()
                ->andReturn(['url' => 'https://oss.example/job-apps/test.pdf']);
        });

        $response = $this->postJson($this->endpoint('hiring-tenant'), [
            'name' => 'Applicant Name',
            'phone' => '+966500000002',
            'email' => 'applicant@example.com',
            'description' => 'Cover letter text',
            'pdf' => $this->validPdf(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['id']]);

        $id = $response->json('data.id');
        $this->assertNotEmpty($id);

        $this->assertDatabaseHas('job_applications', [
            'user_id' => $tenant->id,
            'name' => 'Applicant Name',
            'phone' => '+966500000002',
            'email' => 'applicant@example.com',
            'pdf_path' => 'https://oss.example/job-apps/test.pdf',
        ]);
    }

    public function test_oss_upload_failure_returns_500(): void
    {
        User::factory()->create([
            'username' => 'hiring-tenant-2',
        ]);

        $this->mock(AlibabaOssService::class, function ($mock) {
            $mock->shouldReceive('uploadFile')
                ->once()
                ->andThrow(new \RuntimeException('OSS unavailable'));
        });

        $response = $this->postJson($this->endpoint('hiring-tenant-2'), [
            'name' => 'Applicant',
            'phone' => '+966500000003',
            'email' => 'a@example.com',
            'pdf' => $this->validPdf(),
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Failed to upload PDF.');
    }

    /**
     * Rule: pdf max 5120 KB — add assertion once the suite runs against a migrated DB.
     */
    public function test_oversized_pdf_rejected_with_422_placeholder(): void
    {
        $this->markTestIncomplete(
            'Optional: post a fake PDF > 5120 KB and assert 422 on the pdf attribute.'
        );
    }

    /**
     * Handler must render ThrottleRequestsException as 429 + RATE_LIMITED (not 500 INTERNAL_ERROR).
     */
    public function test_throttle_exception_renders_429_with_rate_limited_code(): void
    {
        $request = Request::create(
            'http://localhost/api/v1/tenant-website/therc/job-applications',
            'POST',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );

        $route = $this->app['router']->getRoutes()->match($request);
        $request->setRouteResolver(static fn () => $route);

        $exception = new ThrottleRequestsException('Too Many Attempts.');
        $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $base = $handler->render($request, $exception);

        TestResponse::fromBaseResponse($base)
            ->assertStatus(429)
            ->assertJsonPath('code', 'RATE_LIMITED')
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('tenant_id', 'therc');
    }
}

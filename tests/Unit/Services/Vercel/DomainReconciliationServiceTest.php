<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Vercel;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DomainReconciliationService;
use App\Services\Vercel\VercelDomainCache;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainInventoryService;
use App\Services\Vercel\VercelMutationGuard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainReconciliationServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function vercel_only_orphan_excludes_legacy_table_domains(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('user_custom_domains')) {
            $this->fail('Required domain tables are missing.');
        }

        $this->configureVercel();

        DB::table('user_custom_domains')->insert([
            'user_id' => User::factory()->tenant()->create()->id,
            'requested_domain' => 'legacy-only.example.com',
            'current_domain' => 'legacy-only.example.com',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => 'legacy-only.example.com', 'verified' => true],
                ],
                'pagination' => ['count' => 1, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $service = $this->makeService();
        $report = $service->buildReport(fetchFresh: true);

        $this->assertSame(0, $report['summary']['vercel_only_orphan']);
        $this->assertCount(1, $report['apex_with_optional_www']);
    }

    /** @test */
    public function platform_domains_are_not_classified_as_orphans(): void
    {
        if (! Schema::hasTable('api_domains_settings')) {
            $this->fail('Required domain tables are missing.');
        }

        $this->configureVercel();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => 'taearif.com', 'verified' => true],
                    ['name' => 'orphan-only.example.com', 'verified' => false],
                ],
                'pagination' => ['count' => 2, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $service = $this->makeService();
        $report = $service->buildReport(fetchFresh: true);

        $this->assertSame(1, $report['summary']['vercel_only_orphan']);
        $this->assertSame(1, $report['summary']['protected_platform']);
        $this->assertSame('orphan-only.example.com', $report['vercel_only_orphan'][0]['apex']);
    }

    private function makeService(): DomainReconciliationService
    {
        return new DomainReconciliationService(
            app(VercelDomainClient::class),
            app(VercelDomainInventoryService::class),
            app(VercelDomainCache::class),
            app(VercelMutationGuard::class)
        );
    }

    private function configureVercel(): void
    {
        config([
            'services.vercel.token' => 'test-token',
            'services.vercel.project_id' => 'prj_test',
            'services.vercel.team_id' => 'team_test',
            'services.vercel.expected_project_id' => 'prj_test',
            'services.vercel.expected_team_id' => 'team_test',
            'services.vercel.base_url' => 'https://api.vercel.com',
            'services.vercel.platform_domains' => [
                'taearif.com',
                'www.taearif.com',
                'bigrises.com',
                'www.bigrises.com',
                'test.taearif.com',
                'mandhoor.com',
            ],
        ]);
    }
}

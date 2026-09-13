<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReconcileVercelDomainsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! collect(Artisan::all())->has('domains:reconcile-vercel')) {
            $this->fail('domains:reconcile-vercel command is not registered.');
        }
    }

    /** @test */
    public function report_only_lists_db_only_domains_missing_from_vercel(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        $domain = $this->seedDomain('db-only-reconcile.example.com');

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => 'vercel-only.example.com', 'verified' => true],
                ],
                'pagination' => ['count' => 1, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $this->artisan('domains:reconcile-vercel')
            ->expectsOutputToContain('db_only')
            ->expectsOutputToContain($domain->custom_name)
            ->assertExitCode(0);
    }

    /** @test */
    public function report_only_lists_vercel_only_orphans(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => 'vercel-orphan.example.com', 'verified' => false],
                ],
                'pagination' => ['count' => 1, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $this->artisan('domains:reconcile-vercel')
            ->expectsOutputToContain('vercel_only')
            ->expectsOutputToContain('vercel-orphan.example.com')
            ->assertExitCode(0);
    }

    /** @test */
    public function json_output_includes_mismatch_categories(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        $this->seedDomain('json-reconcile.example.com');

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [],
                'pagination' => ['count' => 0, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        Artisan::call('domains:reconcile-vercel', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('db_only', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('protected_platform', $report['summary']);
        $this->assertArrayHasKey('legacy_table_orphan', $report['summary']);
    }

    /** @test */
    public function platform_allowlist_domains_are_reported_as_protected_not_orphans(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => 'taearif.com', 'verified' => true],
                    ['name' => 'orphan-only.example.com', 'verified' => false],
                ],
                'pagination' => ['count' => 2, 'next' => null],
            ], 200),
        ]);

        Artisan::call('domains:reconcile-vercel', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(1, $report['summary']['protected_platform']);
        $this->assertSame(1, $report['summary']['vercel_only_orphan']);
        $this->assertSame('orphan-only.example.com', $report['vercel_only_orphan'][0]['apex']);
    }

    /** @test */
    public function wildcard_domains_are_excluded_from_orphan_classification(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => '*.example.com', 'verified' => true],
                ],
                'pagination' => ['count' => 1, 'next' => null],
            ], 200),
        ]);

        $this->artisan('domains:reconcile-vercel', ['--json' => true])
            ->expectsOutputToContain('"vercel_only_orphan": 0')
            ->assertExitCode(0);
    }

    /** @test */
    public function legacy_table_domains_are_recognized_and_not_reported_as_vercel_only_orphans(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        if (! Schema::hasTable('user_custom_domains')) {
            $this->fail('Required legacy domain table is missing.');
        }

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
                'pagination' => ['count' => 1, 'next' => null],
            ], 200),
        ]);

        Artisan::call('domains:reconcile-vercel', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(0, $report['summary']['vercel_only_orphan']);
        $this->assertSame(1, $report['summary']['apex_with_optional_www']);
    }

    /** @test */
    public function remove_apex_requires_matching_confirm_flag(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => 'orphan-remove.example.com', 'verified' => false],
                ],
                'pagination' => ['count' => 1, 'next' => null],
            ], 200),
        ]);

        $this->artisan('domains:reconcile-vercel', [
            '--remove-apex' => 'orphan-remove.example.com',
            '--force-production' => true,
        ])->assertExitCode(1);
    }

    /** @test */
    public function remove_apex_rejects_protected_platform_domains(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        Http::fake([
            'api.vercel.com/v9/projects/prj_test*' => Http::response([
                'id' => 'prj_test',
                'accountId' => 'team_test',
            ], 200),
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => 'taearif.com', 'verified' => true],
                ],
                'pagination' => ['count' => 1, 'next' => null],
            ], 200),
        ]);

        $this->artisan('domains:reconcile-vercel', [
            '--remove-apex' => 'taearif.com',
            '--confirm-apex' => 'taearif.com',
            '--force-production' => true,
        ])->assertExitCode(1);
    }

    /** @test */
    public function remove_apex_fails_when_project_identity_does_not_match(): void
    {
        $this->skipIfMissingSchema();
        config([
            'services.vercel.token' => 'test-token',
            'services.vercel.project_id' => 'prj_test',
            'services.vercel.team_id' => 'team_test',
            'services.vercel.expected_project_id' => 'prj_expected',
            'services.vercel.expected_team_id' => 'team_test',
            'services.vercel.base_url' => 'https://api.vercel.com',
            'services.vercel.platform_domains' => ['taearif.com'],
        ]);

        Http::fake([
            'api.vercel.com/v9/projects/prj_test*' => Http::response([
                'id' => 'prj_actual',
                'accountId' => 'team_test',
            ], 200),
        ]);

        $this->artisan('domains:reconcile-vercel', [
            '--remove-apex' => 'orphan-remove.example.com',
            '--confirm-apex' => 'orphan-remove.example.com',
            '--force-production' => true,
        ])->assertExitCode(1);
    }

    /** @test */
    public function remove_apex_only_targets_explicit_orphans(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response(['id' => 'prj_test', 'accountId' => 'team_test'], 200);
            }

            if ($method === 'GET' && str_contains($url, '/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [
                        ['name' => 'explicit-orphan.example.com', 'verified' => false],
                        ['name' => 'keep-orphan.example.com', 'verified' => false],
                    ],
                    'pagination' => ['count' => 2, 'next' => null],
                ], 200);
            }

            if ($method === 'DELETE' && str_contains($url, 'explicit-orphan.example.com')) {
                return Http::response(null, 200);
            }

            if ($method === 'GET' && str_contains($url, '/domains/')) {
                return Http::response(['error' => 'not_found'], 404);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $exitCode = Artisan::call('domains:reconcile-vercel', [
            '--remove-apex' => 'explicit-orphan.example.com',
            '--confirm-apex' => 'explicit-orphan.example.com',
            '--force-production' => true,
        ]);

        $this->assertSame(0, $exitCode);

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), 'explicit-orphan.example.com'));
        Http::assertNotSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), 'keep-orphan.example.com'));
    }

    /** @test */
    public function command_fails_when_provider_inventory_request_fails(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        Http::fake([
            'api.vercel.com/*' => Http::response(['error' => ['message' => 'upstream']], 500),
        ]);

        $this->artisan('domains:reconcile-vercel')
            ->assertExitCode(1);
    }

    private function skipIfMissingSchema(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('users')) {
            $this->fail('Required domain tables are missing.');
        }
    }

    private function configureVercel(): void
    {
        config([
            'services.vercel.token' => 'test-token',
            'services.vercel.project_id' => 'prj_test',
            'services.vercel.team_id' => 'team_test',
            'services.vercel.expected_project_id' => 'prj_test',
            'services.vercel.expected_team_id' => 'team_test',
            'services.vercel.allow_shared_project_mutations' => true,
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

    private function seedDomain(string $customName): ApiDomainSetting
    {
        $user = User::factory()->tenant()->create([
            'email' => 'reconcile-' . uniqid('', true) . '@example.com',
        ]);

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $customName,
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'vercel_attached' => false,
                    'vercel_verified' => false,
                    'nameservers_ok' => false,
                    'auto_attach_custom_domain' => true,
                    'nameserver_check_enabled' => true,
                ],
            ],
        ]);
    }
}

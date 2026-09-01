<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
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
            $this->markTestSkipped('Phase B2: domains:reconcile-vercel command not implemented.');
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
                    ['name' => 'vercel-only.example.com'],
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
                    ['name' => 'vercel-orphan.example.com'],
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

        $this->artisan('domains:reconcile-vercel', ['--json' => true])
            ->expectsOutputToContain('"db_only"')
            ->assertExitCode(0);
    }

    private function skipIfMissingSchema(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('users')) {
            $this->markTestSkipped('Missing required DB tables.');
        }
    }

    private function configureVercel(): void
    {
        config([
            'services.vercel.token' => 'test-token',
            'services.vercel.project_id' => 'prj_test',
            'services.vercel.team_id' => 'team_test',
            'services.vercel.base_url' => 'https://api.vercel.com',
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

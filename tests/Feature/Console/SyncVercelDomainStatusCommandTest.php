<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DomainProvisioningService;
use App\Services\Vercel\VercelDomainClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class SyncVercelDomainStatusCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.vercel.token' => 'test-token',
            'services.vercel.project_id' => 'prj_test',
            'services.vercel.team_id' => 'team_test',
            'services.vercel.auto_attach_custom_domain' => true,
            'services.vercel.check_nameservers' => true,
            'services.vercel.sync_pace_us' => 0,
            'services.vercel.sync_verify_pace_us' => 0,
        ]);
    }

    /** @test */
    public function sync_command_continues_batch_when_one_domain_throws(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('users')) {
            $this->fail('Required domain tables are missing.');
        }

        ApiDomainSetting::create([
            'user_id' => User::factory()->tenant()->create([
                'email' => 'sync-cmd-bad-' . uniqid('', true) . '@example.com',
            ])->id,
            'custom_name' => 'sync-cmd-bad.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $goodDomain = ApiDomainSetting::create([
            'user_id' => User::factory()->tenant()->create([
                'email' => 'sync-cmd-ok-' . uniqid('', true) . '@example.com',
            ])->id,
            'custom_name' => 'sync-cmd-ok.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $this->mock(VercelDomainClient::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('listProjectDomains')->andReturn(['domains' => []]);
            $mock->shouldReceive('normalizeApex')->andReturnUsing(fn (string $name) => strtolower(trim($name)));
        });

        $this->mock(DomainProvisioningService::class, function ($mock) {
            $mock->shouldReceive('run')
                ->with('sync-cmd-bad.example.com', DomainProvisioningService::MODE_SCHEDULED)
                ->once()
                ->andThrow(new RuntimeException('simulated per-domain failure'));

            $mock->shouldReceive('run')
                ->with('sync-cmd-ok.example.com', DomainProvisioningService::MODE_SCHEDULED)
                ->once()
                ->andReturn([
                    'outcome' => 'active',
                    'health' => 'apex_only',
                    'ssl' => true,
                    'retryable' => false,
                    'message' => 'Domain is active.',
                    'provisioning' => ['mode' => 'scheduled'],
                    'last_check' => [
                        'health_code' => 'apex_only',
                        'ssl_ready' => true,
                        'zone_enabled' => true,
                        'apex_verified' => true,
                        'nameservers_ok' => true,
                        'auto_attach_custom_domain' => true,
                        'nameserver_check_enabled' => true,
                    ],
                ]);
        });

        $exitCode = Artisan::call('domains:sync-vercel-status');

        $this->assertSame(0, $exitCode);
        $goodDomain->refresh();
        $this->assertSame('active', $goodDomain->status);
    }
}

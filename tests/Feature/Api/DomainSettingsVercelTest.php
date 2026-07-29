<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DnsNameserverChecker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DomainSettingsVercelTest extends TestCase
{
    use DatabaseTransactions;

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
            'services.vercel.nameservers' => [
                'ns1.vercel-dns.com',
                'ns2.vercel-dns.com',
            ],
        ]);
    }

    private function actingTenant(): User
    {
        $tenant = User::factory()->tenant()->create([
            'email' => 'domain-tenant-' . uniqid('', true) . '@example.com',
        ]);
        Sanctum::actingAs($tenant);

        return $tenant;
    }

    private function mockNameservers(bool $ok): void
    {
        $this->mock(DnsNameserverChecker::class, function ($mock) use ($ok) {
            $mock->shouldReceive('hasExpectedNameservers')->andReturn($ok);
        });
    }

    public function test_store_creates_pending_and_calls_vercel(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            if (str_contains($url, '/v10/projects/') && str_contains($url, '/domains') && $request->method() === 'POST' && ! str_contains($url, '/verify')) {
                return Http::response(['name' => 'mybrand.com', 'verified' => false], 200);
            }
            if (str_contains($url, '/verify')) {
                return Http::response(['name' => 'mybrand.com', 'verified' => false], 200);
            }
            if (str_contains($url, '/v9/projects/') && str_contains($url, '/domains/')) {
                return Http::response(['name' => 'mybrand.com', 'verified' => false], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'mybrand.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.custom_name', 'mybrand.com')
            ->assertJsonPath('verification.verified', false)
            ->assertJsonPath('verification.nameservers_ok', false)
            ->assertJsonPath('verification.status', 'pending')
            ->assertJsonPath('dnsInstructions.mode', 'nameservers');

        $this->assertNotEmpty($response->json('verification.message'));

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'mybrand.com',
            'status' => 'pending',
        ]);
    }

    public function test_store_returns_verified_when_nameservers_already_ok(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            if (str_contains($url, '/v10/projects/') && $request->method() === 'POST' && ! str_contains($url, '/verify')) {
                return Http::response(['name' => 'ready.example.com', 'verified' => true], 200);
            }
            if (str_contains($url, '/verify') || (str_contains($url, '/v9/projects/') && str_contains($url, '/domains/'))) {
                return Http::response(['name' => 'ready.example.com', 'verified' => true], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'ready.example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.ssl', true)
            ->assertJsonPath('verification.verified', true)
            ->assertJsonPath('verification.nameservers_ok', true)
            ->assertJsonPath('verification.status', 'active');

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'ready.example.com',
            'status' => 'active',
        ]);
    }

    public function test_store_without_vercel_config_returns_503_and_does_not_persist(): void
    {
        $this->skipIfMissingSchema();
        config([
            'services.vercel.token' => null,
            'services.vercel.project_id' => null,
        ]);
        $tenant = $this->actingTenant();

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'noconfig.example.com',
        ]);

        $response->assertStatus(503);
        $this->assertDatabaseMissing('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'noconfig.example.com',
        ]);
    }

    public function test_store_rolls_back_when_vercel_fails(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        Http::fake([
            'api.vercel.com/*' => Http::response(['error' => ['message' => 'boom']], 500),
        ]);

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'fail.example.com',
        ]);

        $response->assertStatus(502);
        $this->assertDatabaseMissing('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'fail.example.com',
        ]);
    }

    public function test_verify_success_sets_active_and_ssl(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'ok.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        Http::fake([
            'api.vercel.com/v9/projects/prj_test/domains/ok.example.com/verify*' => Http::response([
                'name' => 'ok.example.com',
                'verified' => true,
            ], 200),
        ]);

        $response = $this->postJson('/api/settings/domain/verify', [
            'id' => $domain->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.verificationStatus', 'verified');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertTrue((bool) $domain->ssl);
    }

    public function test_verify_not_ready_stays_pending(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'pending.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        Http::fake([
            'api.vercel.com/v9/projects/prj_test/domains/pending.example.com/verify*' => Http::response([
                'name' => 'pending.example.com',
                'verified' => false,
            ], 200),
        ]);

        $response = $this->postJson('/api/settings/domain/verify', [
            'id' => $domain->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.verificationStatus', 'pending');

        $domain->refresh();
        $this->assertSame('pending', $domain->status);
        $this->assertFalse((bool) $domain->ssl);
    }

    public function test_destroy_removes_from_vercel_and_db(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'gone.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        Http::fake([
            'api.vercel.com/v9/projects/prj_test/domains/*' => Http::response(null, 200),
        ]);

        $response = $this->deleteJson('/api/settings/domain/' . $domain->id);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('api_domains_settings', ['id' => $domain->id]);
        Http::assertSentCount(2);
    }

    public function test_index_returns_nameserver_instructions(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->actingTenant();

        $response = $this->getJson('/api/settings/domain');

        $response->assertOk()
            ->assertJsonPath('dnsInstructions.mode', 'nameservers')
            ->assertJsonPath('dnsInstructions.nameservers.0', 'ns1.vercel-dns.com');
    }

    public function test_sync_command_activates_pending_when_ready(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);

        $tenant = User::factory()->tenant()->create([
            'email' => 'sync-tenant-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'sync-ok.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        Http::fake([
            'api.vercel.com/v9/projects/prj_test/domains/sync-ok.example.com/verify*' => Http::response([
                'name' => 'sync-ok.example.com',
                'verified' => true,
            ], 200),
        ]);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertTrue((bool) $domain->ssl);
    }

    public function test_sync_command_fails_active_when_missing_on_vercel(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);

        $tenant = User::factory()->tenant()->create([
            'email' => 'sync-fail-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'missing.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        Http::fake([
            'api.vercel.com/v9/projects/prj_test/domains/missing.example.com*' => Http::response(['error' => 'not_found'], 404),
        ]);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('failed', $domain->status);
        $this->assertFalse((bool) $domain->ssl);
    }

    public function test_sync_command_fails_when_expires_at_past(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        $tenant = User::factory()->tenant()->create([
            'email' => 'sync-exp-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'expired.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('failed', $domain->status);
        $this->assertFalse((bool) $domain->ssl);
    }

    public function test_sync_command_leaves_active_unchanged_when_still_ok(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);

        $tenant = User::factory()->tenant()->create([
            'email' => 'sync-ok2-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'still.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        Http::fake([
            'api.vercel.com/v9/projects/prj_test/domains/still.example.com*' => Http::response([
                'name' => 'still.example.com',
                'verified' => true,
            ], 200),
        ]);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertTrue((bool) $domain->ssl);
    }
}

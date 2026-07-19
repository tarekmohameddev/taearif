<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Models\TenantGlobalComponent;
use App\Models\TenantPage;
use App\Models\TenantWebsiteSavePagesLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SavePagesActivityLogTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')
            || ! Schema::hasTable('tenant_pages')
            || ! Schema::hasTable('tenant_website_save_pages_logs')) {
            $this->markTestSkipped(
                'Requires taearif_testing with users, tenant_pages, and tenant_website_save_pages_logs tables.'
            );
        }
    }

    protected function createTenant(string $username = 'acme'): User
    {
        $user = User::factory()->create();
        $user->username = $username;
        $user->save();

        return $user;
    }

    public function test_save_pages_creates_activity_log_with_login_session_meta_and_whitelisted_fields(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');

        TenantGlobalComponent::create([
            'id' => (string) Str::uuid(),
            'user_id' => $tenant->id,
            'data' => ['header' => ['old' => true]],
        ]);
        TenantPage::create([
            'id' => (string) Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'homepage',
            'components' => [['id' => 'old-c1', 'position' => 0]],
        ]);

        $loginSessionMeta = [
            'loginSource' => 'User',
            'loginIp' => '102.46.136.229',
            'isDevelopment' => true,
            'isLocalhost' => true,
            'loginAt' => '2026-07-12T13:28:22.487Z',
            'loginAtMs' => 1783862902487,
        ];

        $response = $this->postJson('/api/v1/tenant-website/save-pages', [
            'tenantId' => 'acme',
            'pages' => [
                'homepage' => [
                    ['id' => 'c1', 'type' => 'hero', 'name' => 'Hero', 'componentName' => 'hero1', 'data' => [], 'position' => 0],
                ],
            ],
            'globalComponentsData' => ['header' => ['new' => true]],
            'loginSessionMeta' => $loginSessionMeta,
        ]);

        $response->assertOk();

        $this->assertSame(1, TenantWebsiteSavePagesLog::count());

        $log = TenantWebsiteSavePagesLog::first();

        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame('acme', $log->username);
        $this->assertSame('acme', $log->tenant_id_value);
        $this->assertSame($loginSessionMeta, $log->login_session_meta);

        $expectedKeys = ['websiteName', 'domain', 'pages', 'globalComponentsData', 'WebsiteLayout', 'ThemesBackup', 'StaticPages'];
        $this->assertSame($expectedKeys, array_keys($log->before));
        $this->assertSame($expectedKeys, array_keys($log->after));

        // Ensure no unrelated tenant/customer fields leaked into the snapshots.
        $this->assertArrayNotHasKey('email', $log->before);
        $this->assertArrayNotHasKey('password', $log->before);
        $this->assertArrayNotHasKey('email', $log->after);
        $this->assertArrayNotHasKey('password', $log->after);

        $this->assertSame(['old' => true], $log->before['globalComponentsData']['header']);
        $this->assertSame(['new' => true], $log->after['globalComponentsData']['header']);

        $this->assertSame('old-c1', $log->before['pages']['homepage'][0]['id']);
        $this->assertSame('c1', $log->after['pages']['homepage'][0]['id']);
    }

    public function test_save_pages_creates_activity_log_even_when_login_session_meta_is_missing(): void
    {
        $tenant = $this->createTenant('nometatenant');
        $this->actingAs($tenant, 'sanctum');

        $this->postJson('/api/v1/tenant-website/save-pages', [
            'tenantId' => 'nometatenant',
            'pages' => ['homepage' => []],
        ])->assertOk();

        $this->assertSame(1, TenantWebsiteSavePagesLog::count());

        $log = TenantWebsiteSavePagesLog::first();
        $this->assertSame([], $log->login_session_meta);
    }

    public function test_save_pages_logs_every_call_even_when_nothing_changed(): void
    {
        $tenant = $this->createTenant('repeat-tenant');
        $this->actingAs($tenant, 'sanctum');

        $payload = [
            'tenantId' => 'repeat-tenant',
            'pages' => ['homepage' => [
                ['id' => 'c1', 'type' => 'hero', 'name' => 'Hero', 'componentName' => 'hero1', 'data' => [], 'position' => 0],
            ]],
        ];

        $this->postJson('/api/v1/tenant-website/save-pages', $payload)->assertOk();
        $this->postJson('/api/v1/tenant-website/save-pages', $payload)->assertOk();

        $this->assertSame(2, TenantWebsiteSavePagesLog::count());
    }
}

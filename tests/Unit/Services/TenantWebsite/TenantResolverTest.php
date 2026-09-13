<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TenantWebsite;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\TenantWebsite\TenantResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantResolverTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function www_custom_host_resolves_the_same_active_tenant_as_the_apex(): void
    {
        $tenant = User::factory()->tenant()->create([
            'username' => 'resolver-' . uniqid('', false),
            'email' => 'resolver-' . uniqid('', true) . '@example.com',
        ]);

        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'tenant-resolver.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        $request = Request::create('https://www.tenant-resolver.example.com/');
        app(TenantResolver::class)->resolve($request);

        $resolved = $request->attributes->get('tenant_user');
        $this->assertInstanceOf(User::class, $resolved);
        $this->assertSame($tenant->id, $resolved->id);
    }

    /** @test */
    public function www_custom_host_does_not_resolve_non_servable_domain_rows(): void
    {
        $tenant = User::factory()->tenant()->create([
            'username' => 'resolver-pending-' . uniqid('', false),
            'email' => 'resolver-pending-' . uniqid('', true) . '@example.com',
        ]);

        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'tenant-pending.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $request = Request::create('https://www.tenant-pending.example.com/');
        app(TenantResolver::class)->resolve($request);

        $this->assertNull($request->attributes->get('tenant_user'));
    }
}

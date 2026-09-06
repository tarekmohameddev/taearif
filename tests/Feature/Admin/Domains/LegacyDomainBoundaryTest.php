<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Domain\Models\CustomDomain;
use App\Domain\Domain\Services\CustomDomainService;
use App\Exceptions\BusinessLogicException;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Admin\AdminApiTestCase;

class LegacyDomainBoundaryTest extends AdminApiTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function legacy_delete_is_blocked_when_api_domain_setting_exists(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('user_custom_domains')) {
            $this->fail('Required domain tables are missing.');
        }

        $user = User::factory()->tenant()->create([
            'email' => 'legacy-boundary-' . uniqid('', true) . '@example.com',
        ]);

        $domainName = 'legacy-boundary-' . uniqid('', true) . '.example.com';

        $legacy = CustomDomain::create([
            'user_id' => $user->id,
            'requested_domain' => $domainName,
            'current_domain' => $domainName,
            'status' => true,
        ]);

        ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_domain_id' => $legacy->id,
            'custom_name' => $domainName,
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $service = app(CustomDomainService::class);

        try {
            $service->deleteDomain($legacy->id);
            $this->fail('Expected legacy delete to be blocked for Vercel-backed domain.');
        } catch (BusinessLogicException $e) {
            $this->assertSame('VERCEL_BACKED_DOMAIN_DELETE_BLOCKED', $e->getErrorCode());
        }
    }

    /** @test */
    public function legacy_update_cannot_overwrite_domain_identity_when_vercel_backed(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('user_custom_domains')) {
            $this->fail('Required domain tables are missing.');
        }

        $user = User::factory()->tenant()->create([
            'email' => 'legacy-update-' . uniqid('', true) . '@example.com',
        ]);

        $domainName = 'legacy-update-' . uniqid('', true) . '.example.com';

        $legacy = CustomDomain::create([
            'user_id' => $user->id,
            'requested_domain' => $domainName,
            'current_domain' => $domainName,
            'status' => true,
        ]);

        ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_domain_id' => $legacy->id,
            'custom_name' => $domainName,
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $service = app(CustomDomainService::class);

        try {
            $service->updateDomain($legacy->id, [
                'current_domain' => 'other-domain.example.com',
            ]);
            $this->fail('Expected legacy update to be blocked for Vercel-backed domain.');
        } catch (BusinessLogicException $e) {
            $this->assertSame('VERCEL_BACKED_DOMAIN_UPDATE_BLOCKED', $e->getErrorCode());
        }
    }
}

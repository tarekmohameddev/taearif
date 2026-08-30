<?php

namespace Tests\Unit\Calling;

use App\Domain\Calling\Services\CallingLoopbackService;
use Tests\TestCase;

class CallingLoopbackServiceTest extends TestCase
{
    /** @test */
    public function test_it_detects_configured_loopback_tenants(): void
    {
        config([
            'calling.loopback.tenant_ids'    => [1430],
            'calling.loopback.dest_endpoint' => 'agent_1002',
            'calling.loopback.trunk_sentinel'=> 'loopback',
        ]);

        $service = new CallingLoopbackService();

        $this->assertTrue($service->isEnabledForTenant(1430));
        $this->assertFalse($service->isEnabledForTenant(1));
        $this->assertSame('agent_1002', $service->destEndpoint());
        $this->assertSame('loopback', $service->trunkSentinel());
    }

    /** @test */
    public function test_it_is_off_when_no_tenants_configured(): void
    {
        config(['calling.loopback.tenant_ids' => []]);

        $service = new CallingLoopbackService();

        $this->assertFalse($service->isEnabledForTenant(1430));
    }
}

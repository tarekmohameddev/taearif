<?php

namespace Tests\Unit;

use App\Services\MetaGraphService;
use PHPUnit\Framework\TestCase;

class MetaGraphServiceWabaIdsTest extends TestCase
{
    /** @test */
    public function it_collects_unique_waba_ids_from_all_whatsapp_granular_scopes(): void
    {
        $service = new MetaGraphService();
        $response = [
            'data' => [
                'granular_scopes' => [
                    [
                        'scope' => 'whatsapp_business_management',
                        'target_ids' => ['waba-1', 'waba-2'],
                    ],
                    [
                        'scope' => 'whatsapp_business_messaging',
                        'target_ids' => ['waba-2', 'waba-3'],
                    ],
                    [
                        'scope' => 'pages_show_list',
                        'target_ids' => ['not-a-waba'],
                    ],
                ],
            ],
        ];

        $this->assertSame(
            ['waba-1', 'waba-2', 'waba-3'],
            $service->extractWabaIdsFromDebugToken($response)
        );
        $this->assertSame('waba-1', $service->extractWabaIdFromDebugToken($response));
    }

    /** @test */
    public function it_returns_no_waba_ids_for_malformed_granular_scopes(): void
    {
        $service = new MetaGraphService();

        $this->assertSame([], $service->extractWabaIdsFromDebugToken(['data' => []]));
        $this->assertNull($service->extractWabaIdFromDebugToken(['data' => []]));
    }
}

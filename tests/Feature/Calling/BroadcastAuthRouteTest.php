<?php

namespace Tests\Feature\Calling;

use Tests\TestCase;

class BroadcastAuthRouteTest extends TestCase
{
    /** @test */
    public function test_unauthenticated_api_prefix_request_is_rejected(): void
    {
        $this->postJson('/api/broadcasting/auth', [
            'socket_id'    => '1234.5678',
            'channel_name' => 'private-tenant.1',
        ])->assertStatus(401);
    }

    /** @test */
    public function test_unauthenticated_root_path_request_is_rejected(): void
    {
        $this->postJson('/broadcasting/auth', [
            'socket_id'    => '1234.5678',
            'channel_name' => 'private-tenant.1',
        ])->assertStatus(401);
    }
}

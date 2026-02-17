<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OpsHealthEndpointTest extends TestCase
{
    use DatabaseTransactions;

    private function createTenantUser(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'rbac_version' => (int) config('rbac.version', 1),
            'rbac_seeded_at' => now(),
        ]);
    }

    /** @test */
    public function health_returns_tenant_scoped_metrics(): void
    {
        if (! Schema::hasTable('communication_delivery_attempts')) {
            $this->markTestSkipped('communication_delivery_attempts table required.');
        }

        $tenant = $this->createTenantUser();
        Sanctum::actingAs($tenant);

        $res = $this->getJson('/api/v1/communication/ops/health');

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.window_hours', 24)
            ->assertJsonStructure([
                'data' => [
                    'attempts_total',
                    'attempts_failed',
                    'failure_ratio',
                    'due_retry_backlog',
                ],
            ]);

        $data = $res->json('data');
        $this->assertIsInt($data['attempts_total']);
        $this->assertIsInt($data['attempts_failed']);
        $this->assertIsNumeric($data['failure_ratio']);
        $this->assertIsInt($data['due_retry_backlog']);
    }

    /** @test */
    public function health_requires_auth(): void
    {
        $res = $this->getJson('/api/v1/communication/ops/health');
        $res->assertStatus(401);
    }
}

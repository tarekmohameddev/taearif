<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Impersonation;

use App\Domain\Admin\Models\AdminImpersonation;
use App\Domain\User\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Feature\Admin\AdminApiTestCase;

class ImpersonationEndpointsTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_view_active_impersonations(): void
    {
        $admin = $this->signInAdmin();
        $this->createActiveImpersonation($admin);

        $this->getJson(route('admin.api.impersonate.active'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        ['admin', 'user', 'status', 'started_at'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function admin_can_view_impersonation_history(): void
    {
        $admin = $this->signInAdmin();
        $this->createActiveImpersonation($admin);

        $this->getJson(route('admin.api.impersonate.history'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        ['admin', 'user', 'status'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function admin_can_view_user_impersonation_history(): void
    {
        $admin = $this->signInAdmin();
        [$impersonation] = $this->createActiveImpersonation($admin);
        $user = $impersonation->user;

        $this->getJson(route('admin.api.users.impersonate.user-history', $user->id))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        ['admin', 'user'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function admin_can_end_impersonation_session(): void
    {
        $admin = $this->signInAdmin();
        [$impersonation, $plainToken] = $this->createActiveImpersonation($admin);

        $response = $this->postJson(
            route('admin.api.impersonate.exit'),
            headers: ['Authorization' => 'Bearer ' . $plainToken]
        );

        $response->assertOk()
            ->assertJsonPath('data.impersonation.status', 'ended');

        $this->assertEquals('ended', $impersonation->fresh()->status);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $impersonation->token_id,
        ]);
    }

    /**
     * @return array{0: AdminImpersonation, 1: string}
     */
    private function createActiveImpersonation($admin): array
    {
        $user = User::factory()->create();

        $tokenResult = $user->createToken('impersonation-session', ['*'], now()->addHour());
        $plainToken = $tokenResult->plainTextToken;
        $token = $tokenResult->accessToken;

        $impersonation = AdminImpersonation::factory()->create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'token_id' => $token->id,
            'started_at' => now()->subMinutes(5),
            'status' => 'active',
        ]);

        return [$impersonation->fresh(['user']), $plainToken];
    }
}


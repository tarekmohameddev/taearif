<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\Api\UserPropertyRequest;
use App\Models\User;
use App\Models\User\UserCity;

/**
 * E2E: Property Request (public) → Dashboard update flow.
 * POST public → login (tenant) → GET property-requests → PUT status → PUT employee.
 */
class PropertyRequestPublicDashboardTest extends ApiE2ETestCase
{
    /** @test */
    public function full_journey_public_request_then_tenant_list_and_update(): void
    {
        $this->fakeRecaptcha();
        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr',
        ]);

        $city = UserCity::first();
        $this->assertNotNull($city, 'Test DB should have at least one user_cities row');

        // 1. Public submit (no auth)
        $publicSubmit = $this->postJson('/api/v1/property-requests/public', [
            'tenant_username' => 'e2e-tenant-pr',
            'full_name' => 'Public Requester',
            'phone' => '+966509998877',
            'region' => $city->id,
        ]);

        $publicSubmit->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['id']]);

        $requestId = $publicSubmit->json('data.id');

        // 2. Login as tenant
        $login = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $tenant->email,
            'password' => 'password',
        ]);
        $login->assertOk();
        $token = $login->json('token');

        // 3. GET property-requests
        $list = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/property-requests');

        $list->assertOk()
            ->assertJsonPath('status', 'success');
        $this->assertArrayHasKey('data', $list->json());

        // 4. PUT status (if status model exists)
        $statusId = \App\Models\PropertyRequestStatus::first()?->id;
        if ($statusId) {
            $putStatus = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->putJson('/api/v1/property-requests/' . $requestId . '/status', [
                    'status_id' => $statusId,
                ]);
            $putStatus->assertOk();
        }

        // 5. PUT employee (optional: assign employee; may 404 if no employees)
        $employee = User::where('tenant_id', $tenant->id)->where('account_type', 'employee')->first();
        if ($employee) {
            $this->withHeader('Authorization', 'Bearer ' . $token)
                ->putJson('/api/v1/property-requests/' . $requestId . '/employee', [
                    'employee_id' => $employee->id,
                ])
                ->assertOk();
        }
    }
}

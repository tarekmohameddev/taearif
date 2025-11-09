<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Domain\Models\CustomDomain;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateDomainTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_a_domain(): void
    {
        $domain = CustomDomain::factory()->create([
            'requested_domain' => 'tenant.example.com',
            'current_domain' => null,
            'status' => false,
        ]);

        $this->signInAdmin();

        $payload = [
            'requested_domain' => 'updated.example.com',
            'current_domain' => 'current.example.com',
            'status' => true,
        ];

        $response = $this->putJson(
            route('admin.api.domains.update', $domain->id),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.requested_domain', 'updated.example.com')
            ->assertJsonPath('data.current_domain', 'current.example.com')
            ->assertJsonPath('data.status', true)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.is_approved', true);

        $this->assertDatabaseHas('user_custom_domains', [
            'id' => $domain->id,
            'requested_domain' => 'updated.example.com',
            'current_domain' => 'current.example.com',
            'status' => 1,
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_payload(): void
    {
        $domain = CustomDomain::factory()->create();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.domains.update', $domain->id),
            ['requested_domain' => 'not-a-domain']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['requested_domain']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $domain = CustomDomain::factory()->create();

        $response = $this->putJson(
            route('admin.api.domains.update', $domain->id),
            ['requested_domain' => 'updated.example.com']
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_domain_does_not_exist(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.domains.update', 999999),
            ['requested_domain' => 'updated.example.com']
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}


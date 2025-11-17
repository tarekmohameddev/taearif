<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Domain\Models\CustomDomain;
use App\Models\Api\ApiDomainSetting;
use App\Models\User as TenantUser;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageDomainsTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_domains(): void
    {
        $this->signInAdmin();

        CustomDomain::factory()->count(2)->create();

        $response = $this->getJson(route('admin.api.domains.index'));

        $response->assertOk()
            ->assertJsonPath('data.data.0.id', CustomDomain::first()->id)
            ->assertJsonPath('data.meta.total', 2);
    }

    /** @test */
    public function domain_list_includes_primary_and_ssl_metadata(): void
    {
        $this->signInAdmin();

        $domain = CustomDomain::factory()->create([
            'current_domain' => 'tenant-primary.example.com',
        ]);

        ApiDomainSetting::create([
            'user_id' => $domain->user_id,
            'custom_domain_id' => $domain->id,
            'name' => 'Tenant Primary',
            'custom_name' => $domain->current_domain,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now()->toDateString(),
        ]);

        $response = $this->getJson(route('admin.api.domains.index'));

        $response->assertOk()
            ->assertJsonPath('data.data.0.is_primary', true)
            ->assertJsonPath('data.data.0.status_key', 'active')
            ->assertJsonPath('data.data.0.status_source', 'api')
            ->assertJsonPath('data.data.0.ssl_enabled', true)
            ->assertJsonPath('data.data.0.added_date', now()->toDateString());
    }

    /** @test */
    public function domain_list_can_be_filtered_by_status_ssl_and_primary(): void
    {
        $this->signInAdmin();

        $activeDomain = CustomDomain::factory()->create([
            'current_domain' => 'active.example.com',
        ]);
        ApiDomainSetting::create([
            'user_id' => $activeDomain->user_id,
            'custom_domain_id' => $activeDomain->id,
            'name' => 'Active Domain',
            'custom_name' => $activeDomain->current_domain,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now()->subDay()->toDateString(),
        ]);

        $failedDomain = CustomDomain::factory()->create([
            'current_domain' => 'failed.example.com',
        ]);
        ApiDomainSetting::create([
            'user_id' => $failedDomain->user_id,
            'custom_domain_id' => $failedDomain->id,
            'name' => 'Failed Domain',
            'custom_name' => $failedDomain->current_domain,
            'status' => 'failed',
            'primary' => false,
            'ssl' => false,
            'added_date' => now()->toDateString(),
        ]);

        $this->getJson(route('admin.api.domains.index', ['status' => 'active']))
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $activeDomain->id);

        $this->getJson(route('admin.api.domains.index', ['ssl' => false]))
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $failedDomain->id);

        $this->getJson(route('admin.api.domains.index', ['primary' => true]))
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $activeDomain->id);
    }

    /** @test */
    public function listing_domains_requires_authentication(): void
    {
        $this->getJson(route('admin.api.domains.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_create_a_custom_domain(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
            'account_type' => 'tenant',
        ]);

        $payload = [
            'user_id' => $tenant->id,
            'requested_domain' => 'tenant.example.com',
        ];

        $response = $this->postJson(route('admin.api.domains.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.requested_domain', 'tenant.example.com')
            ->assertJsonPath('data.is_pending', true);

        $this->assertDatabaseHas('user_custom_domains', [
            'user_id' => $tenant->id,
            'requested_domain' => 'tenant.example.com',
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_when_creating_domain_with_invalid_payload(): void
    {
        $this->signInAdmin();

        $this->postJson(route('admin.api.domains.store'), [
            'user_id' => 999,
            'requested_domain' => 'invalid_domain',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'requested_domain']);
    }

    /** @test */
    public function admin_can_view_a_domain(): void
    {
        $this->signInAdmin();

        $domain = CustomDomain::factory()->create([
            'requested_domain' => 'view.example.com',
        ]);

        $response = $this->getJson(route('admin.api.domains.show', $domain->id));

        $response->assertOk()
            ->assertJsonPath('data.id', $domain->id)
            ->assertJsonPath('data.requested_domain', 'view.example.com');
    }

    /** @test */
    public function viewing_a_domain_requires_authentication(): void
    {
        $this->getJson(route('admin.api.domains.show', 1))
            ->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_viewing_missing_domain(): void
    {
        $this->signInAdmin();

        $this->getJson(route('admin.api.domains.show', 999999))
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    /** @test */
    public function admin_can_delete_a_domain(): void
    {
        $this->signInAdmin();

        $domain = CustomDomain::factory()->create();

        $response = $this->deleteJson(route('admin.api.domains.destroy', $domain->id));

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('user_custom_domains', [
            'id' => $domain->id,
        ]);
    }

    /** @test */
    public function deleting_a_domain_requires_authentication(): void
    {
        $domain = CustomDomain::factory()->create();

        $this->deleteJson(route('admin.api.domains.destroy', $domain->id))
            ->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_deleting_missing_domain(): void
    {
        $this->signInAdmin();

        $this->deleteJson(route('admin.api.domains.destroy', 999999))
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    /** @test */
    public function admin_can_approve_a_domain(): void
    {
        $this->signInAdmin();

        $domain = CustomDomain::factory()->create([
            'requested_domain' => 'pending.example.com',
            'current_domain' => null,
            'status' => false,
        ]);

        $response = $this->postJson(route('admin.api.domains.approve', $domain->id));

        $response->assertOk()
            ->assertJsonPath('data.current_domain', 'pending.example.com')
            ->assertJsonPath('data.status', true);

        $this->assertTrue($domain->fresh()->status);
        $this->assertEquals('pending.example.com', $domain->fresh()->current_domain);
    }

    /** @test */
    public function approving_domain_requires_authentication(): void
    {
        $domain = CustomDomain::factory()->create();

        $this->postJson(route('admin.api.domains.approve', $domain->id))
            ->assertUnauthorized();
    }

    /** @test */
    public function approving_nonexistent_domain_returns_not_found(): void
    {
        $this->signInAdmin();

        $this->postJson(route('admin.api.domains.approve', 999999))
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    /** @test */
    public function approving_already_approved_domain_returns_error(): void
    {
        $this->signInAdmin();

        $domain = CustomDomain::factory()->approved()->create([
            'requested_domain' => 'approved.example.com',
        ]);

        $this->postJson(route('admin.api.domains.approve', $domain->id))
            ->assertStatus(422)
            ->assertJsonPath('code', 'DOMAIN_ALREADY_APPROVED')
            ->assertJsonPath('errors.error_code', 'DOMAIN_ALREADY_APPROVED');
    }

    /** @test */
    public function admin_can_reject_a_domain(): void
    {
        $this->signInAdmin();

        $domain = CustomDomain::factory()->create([
            'requested_domain' => 'reject-me.example.com',
            'status' => false,
        ]);

        $response = $this->postJson(route('admin.api.domains.reject', $domain->id));

        $response->assertOk()
            ->assertJsonPath('data.requested_domain', null)
            ->assertJsonPath('data.status', false);

        $this->assertNull($domain->fresh()->requested_domain);
    }

    /** @test */
    public function rejecting_nonexistent_domain_returns_not_found(): void
    {
        $this->signInAdmin();

        $this->postJson(route('admin.api.domains.reject', 999999))
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    /** @test */
    public function admin_can_toggle_domain_status(): void
    {
        $this->signInAdmin();

        $domain = CustomDomain::factory()->create([
            'status' => false,
        ]);

        $response = $this->postJson(route('admin.api.domains.toggle-status', $domain->id));

        $response->assertOk()
            ->assertJsonPath('data.status', true);

        $this->assertTrue($domain->fresh()->status);
    }

    /** @test */
    public function toggling_domain_status_requires_authentication(): void
    {
        $domain = CustomDomain::factory()->create();

        $this->postJson(route('admin.api.domains.toggle-status', $domain->id))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_domain_statistics(): void
    {
        $this->signInAdmin();

        CustomDomain::factory()->create(['status' => true, 'current_domain' => 'live.example.com']);
        CustomDomain::factory()->create(['requested_domain' => 'pending.example.com', 'current_domain' => null]);

        $response = $this->getJson(route('admin.api.domains.statistics'));

        $response->assertOk()
            ->assertJsonPath('data.total_domains', 2)
            ->assertJsonPath('data.approved_domains', 1)
            ->assertJsonPath('data.pending_requests', 1);
    }
}


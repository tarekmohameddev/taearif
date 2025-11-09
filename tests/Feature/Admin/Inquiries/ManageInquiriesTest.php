<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inquiries;

use App\Domain\Support\Models\Inquiry;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageInquiriesTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_inquiries(): void
    {
        $this->signInAdmin();

        $inquiries = Inquiry::factory()->count(2)->create();

        $response = $this->getJson(route('admin.api.inquiries.index'));

        $response->assertOk()
            ->assertJsonFragment(['id' => $inquiries->first()->id])
            ->assertJsonFragment(['id' => $inquiries->last()->id]);
    }

    /** @test */
    public function listing_inquiries_requires_authentication(): void
    {
        $this->getJson(route('admin.api.inquiries.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_create_an_inquiry(): void
    {
        $this->signInAdmin();

        $tenant = User::factory()->create();

        $payload = [
            'user_id' => $tenant->id,
            'phone_number' => '1234567890',
            'message' => 'Need help with property listing.',
            'inquiry_type' => 'buy',
            'budget' => 5000,
            'currency' => 'USD',
        ];

        $response = $this->postJson(route('admin.api.inquiries.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.inquiry.message', 'Need help with property listing.')
            ->assertJsonPath('data.tenant.id', $tenant->id);

        $this->assertDatabaseHas('api_customer_inquiry', [
            'user_id' => $tenant->id,
            'message' => 'Need help with property listing.',
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_when_creating_inquiry_with_invalid_payload(): void
    {
        $this->signInAdmin();

        $this->postJson(route('admin.api.inquiries.store'), [
            'user_id' => 999,
            'phone_number' => '',
            'message' => '',
            'inquiry_type' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'message']);
    }

    /** @test */
    public function admin_can_view_an_inquiry(): void
    {
        $this->signInAdmin();

        $inquiry = Inquiry::factory()->create([
            'message' => 'Viewing details',
        ]);

        $response = $this->getJson(
            route('admin.api.inquiries.show', $inquiry->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $inquiry->id)
            ->assertJsonPath('data.inquiry.message', 'Viewing details');
    }

    /** @test */
    public function viewing_inquiry_requires_authentication(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->getJson(
            route('admin.api.inquiries.show', $inquiry->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_viewing_missing_inquiry(): void
    {
        $this->signInAdmin();

        $this->getJson(
            route('admin.api.inquiries.show', 999999)
        )->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    /** @test */
    public function admin_can_delete_an_inquiry(): void
    {
        $this->signInAdmin();

        $inquiry = Inquiry::factory()->create();

        $response = $this->deleteJson(
            route('admin.api.inquiries.destroy', $inquiry->id)
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('api_customer_inquiry', [
            'id' => $inquiry->id,
        ]);
    }

    /** @test */
    public function deleting_inquiry_requires_authentication(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->deleteJson(
            route('admin.api.inquiries.destroy', $inquiry->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_inquiry_statistics(): void
    {
        $this->signInAdmin();

        Inquiry::factory()->create(['inquiry_type' => 'buy']);
        Inquiry::factory()->create(['inquiry_type' => 'rent']);

        $response = $this->getJson(route('admin.api.inquiries.statistics'));

        $response->assertOk()
            ->assertJsonPath('data.total', Inquiry::count())
            ->assertJsonPath('data.by_type.buy', 1)
            ->assertJsonPath('data.by_type.rent', 1);
    }

    /** @test */
    public function admin_can_list_inquiries_by_tenant(): void
    {
        $this->signInAdmin();

        $tenant = User::factory()->create();

        Inquiry::factory()->count(2)->create([
            'user_id' => $tenant->id,
        ]);

        $response = $this->getJson(
            route('admin.api.inquiries.by-tenant', $tenant->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.data.0.tenant.id', $tenant->id);
    }

    /** @test */
    public function listing_inquiries_by_tenant_requires_authentication(): void
    {
        $tenant = User::factory()->create();

        $this->getJson(
            route('admin.api.inquiries.by-tenant', $tenant->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_list_inquiries_by_customer(): void
    {
        $this->signInAdmin();

        $tenant = User::factory()->create();

        $customerId = DB::table('api_customers')->insertGetId([
            'user_id' => $tenant->id,
            'name' => 'John Customer',
            'phone_number' => '5551234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Inquiry::factory()->count(2)->create([
            'customer_id' => $customerId,
            'user_id' => $tenant->id,
        ]);

        $response = $this->getJson(
            route('admin.api.inquiries.by-customer', $customerId)
        );

        $response->assertOk()
            ->assertJsonPath('data.data.0.customer.id', $customerId);
    }

    /** @test */
    public function admin_can_bulk_delete_inquiries(): void
    {
        $this->signInAdmin();

        $inquiries = Inquiry::factory()->count(3)->create();

        $response = $this->postJson(
            route('admin.api.inquiries.bulk-delete'),
            ['ids' => $inquiries->pluck('id')->all()]
        );

        $response->assertOk()
            ->assertJsonPath('data.deleted_count', 3);

        foreach ($inquiries as $inquiry) {
            $this->assertDatabaseMissing('api_customer_inquiry', [
                'id' => $inquiry->id,
            ]);
        }
    }

    /** @test */
    public function bulk_delete_requires_valid_payload(): void
    {
        $this->signInAdmin();

        $this->postJson(
            route('admin.api.inquiries.bulk-delete'),
            ['ids' => []]
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['ids']);
    }
}


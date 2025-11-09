<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inquiries;

use App\Domain\Support\Models\Inquiry;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateInquiryTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_an_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create([
            'message' => 'Original message',
            'budget' => 5000,
            'furnished' => false,
            'location' => 'Original City',
        ]);

        $this->signInAdmin();

        $payload = [
            'message' => 'Updated inquiry message',
            'budget' => 7500,
            'furnished' => true,
            'location' => 'Updated City',
            'urgency' => 'high',
        ];

        $response = $this->putJson(
            route('admin.api.inquiries.update', $inquiry->id),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.inquiry.message', 'Updated inquiry message')
            ->assertJsonPath('data.property_details.budget', 7500)
            ->assertJsonPath('data.property_details.furnished', true)
            ->assertJsonPath('data.location.location_text', 'Updated City')
            ->assertJsonPath('data.inquiry.urgency', 'high');

        $this->assertDatabaseHas('api_customer_inquiry', [
            'id' => $inquiry->id,
            'message' => 'Updated inquiry message',
            'budget' => 7500,
            'furnished' => 1,
            'location' => 'Updated City',
            'urgency' => 'high',
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_payload(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.inquiries.update', $inquiry->id),
            [
                'min_area_sqm' => 150,
                'max_area_sqm' => 100,
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['max_area_sqm']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $inquiry = Inquiry::factory()->create();

        $response = $this->putJson(
            route('admin.api.inquiries.update', $inquiry->id),
            ['message' => 'Attempted update']
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_inquiry_does_not_exist(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.inquiries.update', 999999),
            ['message' => 'Updated inquiry message']
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}


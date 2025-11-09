<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Referrals;

use App\Domain\Referral\Models\Affiliate;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateAffiliateTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_an_affiliate(): void
    {
        $affiliate = Affiliate::factory()->create([
            'fullname' => 'Original Affiliate',
            'commission_percentage' => 10,
            'request_status' => 'pending',
        ]);

        $this->signInAdmin();

        $payload = [
            'fullname' => 'Updated Affiliate',
            'commission_percentage' => 15.5,
            'request_status' => 'approved',
            'bank_name' => 'Updated Bank',
        ];

        $response = $this->putJson(
            route('admin.api.referrals.update', $affiliate->id),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.fullname', 'Updated Affiliate')
            ->assertJsonPath('data.commission_percentage', 15.5)
            ->assertJsonPath('data.request_status', 'approved')
            ->assertJsonPath('data.bank_name', 'Updated Bank');

        $this->assertDatabaseHas('api_affiliate_users', [
            'id' => $affiliate->id,
            'fullname' => 'Updated Affiliate',
            'commission_percentage' => 15.5,
            'request_status' => 'approved',
            'bank_name' => 'Updated Bank',
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_payload(): void
    {
        $affiliate = Affiliate::factory()->create();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.referrals.update', $affiliate->id),
            ['commission_percentage' => 150]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['commission_percentage']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $affiliate = Affiliate::factory()->create();

        $response = $this->putJson(
            route('admin.api.referrals.update', $affiliate->id),
            ['fullname' => 'Attempted Update']
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_affiliate_does_not_exist(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.referrals.update', 999999),
            ['fullname' => 'Updated Affiliate']
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}


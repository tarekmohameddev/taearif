<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Referrals;

use App\Domain\Affiliate\Models\Affiliate;
use App\Domain\Affiliate\Models\AffiliateTransaction;
use App\Models\User as TenantUser;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageReferralsTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_affiliates(): void
    {
        $this->signInAdmin();

        $affiliates = Affiliate::factory()->count(2)->create();

        $response = $this->getJson(route('admin.api.affiliates.index'));

        $first = $affiliates->first();
        $expectedJoinDate = $first->start_date_value?->format('Y-m-d');

        $response->assertOk()
            // ->assertJsonFragment(['id' => $first->id])
            // ->assertJsonFragment(['id' => $affiliates->last()->id])
            // ->assertJsonPath('data.affiliates_users.0.id', $first->id)
            ->assertJsonPath('data.affiliates_cards.total_partners', $affiliates->count())
            ->assertJsonPath('data.affiliates_users.0.partner.name', $first->fullname)
            ->assertJsonPath('data.affiliates_users.0.referrals', 0)
            ->assertJsonPath('data.affiliates_users.0.transfers', 0)
            ->assertJsonPath('data.affiliates_users.0.earnings', 0)
            ->assertJsonPath('data.affiliates_users.0.status', $first->request_status)
            ->assertJsonPath('data.affiliates_users.0.joining_date', $expectedJoinDate);
    }

    /** @test */
    public function listing_affiliates_requires_authentication(): void
    {
        $this->getJson(route('admin.api.affiliates.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_create_an_affiliate(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'account_type' => 'tenant',
        ]);

        $payload = [
            'user_id' => $tenant->id,
            'fullname' => 'Referral User',
            'bank_name' => 'Referral Bank',
            'bank_account_number' => '123456789',
            'iban' => 'SA03 8000 0000 6080 1016 7519',
            'commission_percentage' => 10,
        ];

        $response = $this->postJson(route('admin.api.affiliates.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.fullname', 'Referral User')
            ->assertJsonPath('data.commission_percentage', 10);

        $this->assertDatabaseHas('api_affiliate_users', [
            'user_id' => $tenant->id,
            'fullname' => 'Referral User',
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_when_creating_affiliate_with_invalid_payload(): void
    {
        $this->signInAdmin();

        $this->postJson(route('admin.api.affiliates.store'), [
            'user_id' => 999,
            'fullname' => '',
            'bank_name' => '',
            'bank_account_number' => '',
            'iban' => '',
            'commission_percentage' => 150,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'fullname',
                'bank_name',
                'bank_account_number',
                'iban',
                'commission_percentage',
            ]);
    }

    /** @test */
    public function admin_can_view_an_affiliate(): void
    {
        $this->signInAdmin();

        $affiliate = Affiliate::factory()->create([
            'fullname' => 'View Affiliate',
        ]);

        $response = $this->getJson(
            route('admin.api.affiliates.show', $affiliate->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $affiliate->id)
            ->assertJsonPath('data.fullname', 'View Affiliate');
    }

    /** @test */
    public function viewing_affiliate_requires_authentication(): void
    {
        $affiliate = Affiliate::factory()->create();

        $this->getJson(
            route('admin.api.affiliates.show', $affiliate->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_viewing_missing_affiliate(): void
    {
        $this->signInAdmin();

        $this->getJson(
            route('admin.api.affiliates.show', 999999)
        )->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    /** @test */
    public function admin_can_view_referral_statistics(): void
    {
        $this->signInAdmin();

        Affiliate::factory()->create(['request_status' => 'pending']);
        $approvedAffiliate = Affiliate::factory()->create(['request_status' => 'approved']);
        Affiliate::factory()->create(['request_status' => 'rejected']);

        AffiliateTransaction::factory()
            ->for($approvedAffiliate, 'affiliate')
            ->create(['type' => 'pending', 'amount' => 50]);

        AffiliateTransaction::factory()
            ->for($approvedAffiliate, 'affiliate')
            ->collected()
            ->create(['amount' => 200]);

        $response = $this->getJson(route('admin.api.affiliates.statistics'));

        $response->assertOk()
            ->assertJsonPath('data.affiliates.total', 3)
            ->assertJsonPath('data.affiliates.pending', 1)
            ->assertJsonPath('data.affiliates.approved', 1)
            ->assertJsonPath('data.affiliates.rejected', 1)
            ->assertJsonPath('data.transactions.total', 2)
            ->assertJsonPath('data.transactions.pending', 1)
            ->assertJsonPath('data.transactions.collected', 1)
            ->assertJsonPath('data.transactions.total_amount', 250)
            ->assertJsonPath('data.transactions.pending_amount', 50)
            ->assertJsonPath('data.transactions.collected_amount', 200);
    }

    /** @test */
    public function admin_can_list_transactions(): void
    {
        $this->signInAdmin();

        $affiliate = Affiliate::factory()->create();
        $transactions = AffiliateTransaction::factory()
            ->for($affiliate, 'affiliate')
            ->count(2)
            ->create();

        $response = $this->getJson(route('admin.api.affiliates.transactions.index'));

        $response->assertOk()
            ->assertJsonFragment(['id' => $transactions->first()->id])
            ->assertJsonFragment(['id' => $transactions->last()->id]);
    }

    /** @test */
    public function transactions_listing_requires_authentication(): void
    {
        $this->getJson(route('admin.api.affiliates.transactions.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_transaction(): void
    {
        $this->signInAdmin();

        $transaction = AffiliateTransaction::factory()->create([
            'amount' => 33.33,
        ]);

        $response = $this->getJson(
            route('admin.api.affiliates.transactions.show', $transaction->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.amount', 33.33);
    }

    /** @test */
    public function transaction_view_requires_authentication(): void
    {
        $transaction = AffiliateTransaction::factory()->create();

        $this->getJson(
            route('admin.api.affiliates.transactions.show', $transaction->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_collect_pending_transaction(): void
    {
        $this->signInAdmin();

        $transaction = AffiliateTransaction::factory()->create([
            'type' => 'pending',
        ]);

        $response = $this->postJson(
            route('admin.api.affiliates.transactions.collect', $transaction->id),
            ['note' => 'Collected manually']
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'collected')
            ->assertJsonPath('data.note', 'Collected manually');

        $this->assertEquals('collected', $transaction->fresh()->type);
    }

    /** @test */
    public function collecting_non_pending_transaction_returns_error(): void
    {
        $this->signInAdmin();

        $transaction = AffiliateTransaction::factory()->collected()->create();

        $this->postJson(
            route('admin.api.affiliates.transactions.collect', $transaction->id)
        )->assertStatus(422);
    }

    /** @test */
    public function admin_can_update_affiliate_request_status(): void
    {
        $this->signInAdmin();

        $affiliate = Affiliate::factory()->create(['request_status' => 'pending']);

        $response = $this->postJson(
            route('admin.api.affiliates.request-status.update', $affiliate->id),
            ['request_status' => 'approved']
        );

        $response->assertOk()
            ->assertJsonPath('data.request_status', 'approved');

        $this->assertDatabaseHas('api_affiliate_users', [
            'id' => $affiliate->id,
            'request_status' => 'approved',
        ]);
    }

    /** @test */
    public function invalid_affiliate_status_is_rejected(): void
    {
        $this->signInAdmin();

        $affiliate = Affiliate::factory()->create(['request_status' => 'pending']);

        $this->postJson(
            route('admin.api.affiliates.request-status.update', $affiliate->id),
            ['request_status' => 'foo']
        )->assertUnprocessable()
         ->assertJsonValidationErrors(['request_status']);
    }

    /** @test */
    public function updating_affiliate_status_requires_authentication(): void
    {
        $affiliate = Affiliate::factory()->create(['request_status' => 'pending']);

        $this->postJson(
            route('admin.api.affiliates.request-status.update', $affiliate->id),
            ['request_status' => 'approved']
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_affiliate_details(): void
    {
        $this->signInAdmin();

        $affiliate = Affiliate::factory()->create([
            'commission_percentage' => 25,
            'pending_amount' => 1200,
            'request_status' => 'approved',
        ]);

        AffiliateTransaction::factory()->create([
            'affiliate_id' => $affiliate->id,
            'type' => 'pending',
            'amount' => 300,
        ]);

        $collected = AffiliateTransaction::factory()->collected()->create([
            'affiliate_id' => $affiliate->id,
            'amount' => 540.5,
        ]);

        $response = $this->getJson(route('admin.api.affiliates.show', $affiliate->id));

        $response->assertOk()
            ->assertJsonPath('data.partner.name', $affiliate->fullname)
            ->assertJsonPath('data.partner.request_status', 'approved')
            ->assertJsonPath('data.partner.commission_percentage', 25.0)
            ->assertJsonPath('data.cards.total_earnings', 840.5)
            ->assertJsonPath('data.cards.pending_earnings', 1200.0)
            ->assertJsonPath('data.cards.referrals_count', 2)
            ->assertJsonPath('data.cards.transfers_count', 1)
            ->assertJsonPath('data.payouts_history.0.id', $collected->id);
    }
}



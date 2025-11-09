<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Referrals;

use App\Domain\Referral\Models\Affiliate;
use App\Domain\Referral\Models\AffiliateTransaction;
use App\Models\User as TenantUser;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageReferralsTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_affiliates(): void
    {
        $this->signInAdmin();

        $affiliates = Affiliate::factory()->count(2)->create();

        $response = $this->getJson(route('admin.api.referrals.index'));

        $response->assertOk()
            ->assertJsonFragment(['id' => $affiliates->first()->id])
            ->assertJsonFragment(['id' => $affiliates->last()->id]);
    }

    /** @test */
    public function listing_affiliates_requires_authentication(): void
    {
        $this->getJson(route('admin.api.referrals.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_create_an_affiliate(): void
    {
        $this->signInAdmin();

        $tenant = TenantUser::factory()->create([
            'uuid' => (string) Str::uuid(),
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

        $response = $this->postJson(route('admin.api.referrals.store'), $payload);

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

        $this->postJson(route('admin.api.referrals.store'), [
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
            route('admin.api.referrals.show', $affiliate->id)
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
            route('admin.api.referrals.show', $affiliate->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_viewing_missing_affiliate(): void
    {
        $this->signInAdmin();

        $this->getJson(
            route('admin.api.referrals.show', 999999)
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
            ->approved()
            ->create(['amount' => 75]);

        AffiliateTransaction::factory()
            ->for($approvedAffiliate, 'affiliate')
            ->paid()
            ->create(['amount' => 125]);

        $response = $this->getJson(route('admin.api.referrals.statistics'));

        $response->assertOk()
            ->assertJsonPath('data.affiliates.total', 3)
            ->assertJsonPath('data.affiliates.pending', 1)
            ->assertJsonPath('data.affiliates.approved', 1)
            ->assertJsonPath('data.affiliates.rejected', 1)
            ->assertJsonPath('data.transactions.total', 3)
            ->assertJsonPath('data.transactions.pending', 1)
            ->assertJsonPath('data.transactions.approved', 1)
            ->assertJsonPath('data.transactions.paid', 1)
            ->assertJsonPath('data.transactions.total_amount', 250)
            ->assertJsonPath('data.transactions.pending_amount', 50)
            ->assertJsonPath('data.transactions.paid_amount', 125);
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

        $response = $this->getJson(route('admin.api.referrals.transactions.index'));

        $response->assertOk()
            ->assertJsonFragment(['id' => $transactions->first()->id])
            ->assertJsonFragment(['id' => $transactions->last()->id]);
    }

    /** @test */
    public function transactions_listing_requires_authentication(): void
    {
        $this->getJson(route('admin.api.referrals.transactions.index'))
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
            route('admin.api.referrals.transactions.show', $transaction->id)
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
            route('admin.api.referrals.transactions.show', $transaction->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_approve_pending_transaction(): void
    {
        $this->signInAdmin();

        $transaction = AffiliateTransaction::factory()->create([
            'type' => 'pending',
        ]);

        $response = $this->postJson(
            route('admin.api.referrals.transactions.approve', $transaction->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'approved');

        $this->assertEquals('approved', $transaction->fresh()->type);
    }

    /** @test */
    public function approving_non_pending_transaction_returns_error(): void
    {
        $this->signInAdmin();

        $transaction = AffiliateTransaction::factory()->approved()->create();

        $this->postJson(
            route('admin.api.referrals.transactions.approve', $transaction->id)
        )->assertStatus(422);
    }

    /** @test */
    public function admin_can_reject_pending_transaction(): void
    {
        $this->signInAdmin();

        $transaction = AffiliateTransaction::factory()->create([
            'type' => 'pending',
        ]);

        $response = $this->postJson(
            route('admin.api.referrals.transactions.reject', $transaction->id),
            ['note' => 'Invalid receipt']
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'rejected')
            ->assertJsonPath('data.note', 'Invalid receipt');

        $this->assertEquals('rejected', $transaction->fresh()->type);
    }

    /** @test */
    public function rejecting_non_pending_transaction_returns_error(): void
    {
        $this->signInAdmin();

        $transaction = AffiliateTransaction::factory()->approved()->create();

        $this->postJson(
            route('admin.api.referrals.transactions.reject', $transaction->id)
        )->assertStatus(422);
    }

    /** @test */
    public function admin_can_mark_approved_transaction_as_paid(): void
    {
        $this->signInAdmin();

        $transaction = AffiliateTransaction::factory()->approved()->create();

        $response = $this->postJson(
            route('admin.api.referrals.transactions.mark-paid', $transaction->id),
            ['note' => 'Transferred on 2025-01-01']
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'paid')
            ->assertJsonPath('data.note', 'Transferred on 2025-01-01');

        $this->assertEquals('paid', $transaction->fresh()->type);
    }

    /** @test */
    public function marking_non_approved_transaction_as_paid_returns_error(): void
    {
        $this->signInAdmin();

        $transaction = AffiliateTransaction::factory()->create([
            'type' => 'pending',
        ]);

        $this->postJson(
            route('admin.api.referrals.transactions.mark-paid', $transaction->id)
        )->assertStatus(422);
    }
}


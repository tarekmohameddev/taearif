<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Billing;

use App\Domain\Billing\Models\Plan;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\Feature\Admin\AdminApiTestCase;

class ApproveMembershipOverlapTest extends AdminApiTestCase
{
    protected bool $shouldResetAdminData = false;

    /** @test */
    public function approving_pending_invoice_expires_other_active_membership(): void
    {
        $currentPlan = Plan::query()->create([
            'title' => 'Monthly Active',
            'slug' => 'monthly-active-' . uniqid(),
            'price' => 99,
            'term' => 'monthly',
            'status' => '1',
            'is_active' => true,
        ]);
        $newPlan = Plan::query()->create([
            'title' => 'Yearly Pending',
            'slug' => 'yearly-pending-' . uniqid(),
            'price' => 149,
            'term' => 'yearly',
            'status' => '1',
            'is_active' => true,
        ]);

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
        ]);

        $activeMembership = Membership::query()->create([
            'user_id' => $tenant->id,
            'package_id' => $currentPlan->id,
            'price' => 99,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-ACTIVE-' . uniqid(),
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => now()->subMonth()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
        ]);

        $pendingMembership = Membership::query()->create([
            'user_id' => $tenant->id,
            'package_id' => $newPlan->id,
            'price' => 149,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'manual',
            'transaction_id' => 'TX-PENDING-' . uniqid(),
            'status' => 0,
            'is_trial' => 0,
            'trial_days' => 0,
            'start_date' => Carbon::today()->toDateString(),
            'expire_date' => Carbon::today()->addYear()->toDateString(),
        ]);

        $this->signInAdmin();

        $this->postJson(
            route('admin.api.billing.invoices.approve', $pendingMembership->id),
            []
        )->assertOk();

        $activeMembership->refresh();
        $pendingMembership->refresh();

        $this->assertSame(
            now()->subDay()->toDateString(),
            Carbon::parse($activeMembership->expire_date)->toDateString()
        );
        $this->assertSame(1, (int) $pendingMembership->status);
    }
}

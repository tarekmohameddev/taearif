<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Billing;

use App\Domain\Billing\Models\Invoice;
use Illuminate\Support\Carbon;
use Tests\Feature\Admin\AdminApiTestCase;

class ApproveInvoiceTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_approve_a_pending_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 0,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'expire_date' => Carbon::today()->addMonth()->format('Y-m-d'),
        ]);

        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.billing.invoices.approve', $invoice->id),
            [
                'notes' => 'Payment verified manually',
                'send_email' => true,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.status_code', 1)
            ->assertJsonPath('data.amount.total', 99.99);

        $this->assertDatabaseHas('memberships', [
            'id' => $invoice->id,
            'status' => 1,
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_parameters(): void
    {
        $invoice = Invoice::factory()->create();

        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.billing.invoices.approve', $invoice->id),
            [
                'send_email' => 'not-a-boolean',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['send_email']);
    }

    /** @test */
    public function cannot_approve_an_invoice_that_is_already_paid(): void
    {
        $invoice = Invoice::factory()->paid()->create();

        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.billing.invoices.approve', $invoice->id),
            []
        );

        $response->assertStatus(400)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('errors.error_code', 'INVOICE_ALREADY_APPROVED');
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->postJson(
            route('admin.api.billing.invoices.approve', $invoice->id),
            []
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_invoice_does_not_exist(): void
    {
        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.billing.invoices.approve', 999999),
            []
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }
}


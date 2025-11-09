<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Billing;

use App\Domain\Billing\Models\Invoice;
use Tests\Feature\Admin\AdminApiTestCase;

class RejectInvoiceTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_reject_a_pending_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 0,
        ]);

        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.billing.invoices.reject', $invoice->id),
            [
                'reason' => 'Invalid payment receipt',
                'send_email' => false,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.status_code', 2);

        $this->assertDatabaseHas('memberships', [
            'id' => $invoice->id,
            'status' => 2,
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_when_reject_payload_is_invalid(): void
    {
        $invoice = Invoice::factory()->create();

        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.billing.invoices.reject', $invoice->id),
            [
                'send_email' => 'not-a-boolean',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['send_email']);
    }

    /** @test */
    public function cannot_reject_a_paid_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create();

        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.billing.invoices.reject', $invoice->id),
            [
                'reason' => 'Late refund request',
            ]
        );

        $response->assertStatus(400)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('errors.error_code', 'INVOICE_ALREADY_PAID');
    }

    /** @test */
    public function unauthenticated_users_cannot_reject_invoices(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->postJson(
            route('admin.api.billing.invoices.reject', $invoice->id),
            []
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_invoice_cannot_be_found_for_rejection(): void
    {
        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.billing.invoices.reject', 999999),
            [
                'reason' => 'Invoice not present',
            ]
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }
}


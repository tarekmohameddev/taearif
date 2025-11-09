<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Billing;

use App\Domain\Billing\Models\Invoice;
use Illuminate\Support\Carbon;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageBillingTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_invoices(): void
    {
        $this->signInAdmin();

        Invoice::factory()->count(2)->create([
            'status' => 0,
        ]);

        $response = $this->getJson(
            route('admin.api.billing.invoices.index')
        );

        $response->assertOk()
            ->assertJsonPath('data.data.0.id', Invoice::first()->id);
    }

    /** @test */
    public function listing_invoices_requires_authentication(): void
    {
        $this->getJson(route('admin.api.billing.invoices.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_an_invoice(): void
    {
        $this->signInAdmin();

        $invoice = Invoice::factory()->create([
            'transaction_id' => 'INV-001',
            'status' => 1,
        ]);

        $response = $this->getJson(
            route('admin.api.billing.invoices.show', $invoice->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.transaction_id', 'INV-001');
    }

    /** @test */
    public function viewing_invoice_requires_authentication(): void
    {
        $invoice = Invoice::factory()->create();

        $this->getJson(
            route('admin.api.billing.invoices.show', $invoice->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_viewing_missing_invoice(): void
    {
        $this->signInAdmin();

        $this->getJson(
            route('admin.api.billing.invoices.show', 999999)
        )->assertStatus(404);
    }

    /** @test */
    public function admin_can_view_invoice_statistics(): void
    {
        $this->signInAdmin();

        Invoice::factory()->create([
            'status' => 1,
        ]);

        Invoice::factory()->create([
            'status' => 0,
        ]);

        Invoice::factory()->create([
            'status' => 2,
            'is_trial' => true,
        ]);

        $response = $this->getJson(route('admin.api.billing.statistics'));

        $response->assertOk()
            ->assertJsonPath('data.invoices.total', 3)
            ->assertJsonPath('data.invoices.paid', 1)
            ->assertJsonPath('data.invoices.pending', 1)
            ->assertJsonPath('data.invoices.rejected', 1)
            ->assertJsonPath('data.invoices.trial', 1);
    }

    /** @test */
    public function admin_can_view_revenue_for_a_date_range(): void
    {
        $this->signInAdmin();

        Invoice::factory()->create([
            'status' => 1,
            'price' => 50.00,
            'created_at' => Carbon::parse('2025-01-05'),
        ]);

        Invoice::factory()->create([
            'status' => 1,
            'price' => 75.50,
            'created_at' => Carbon::parse('2025-01-10'),
        ]);

        Invoice::factory()->create([
            'status' => 0,
            'price' => 200.00,
            'created_at' => Carbon::parse('2025-01-12'),
        ]);

        $response = $this->getJson(
            route('admin.api.billing.revenue', [
                'from' => '2025-01-01',
                'to' => '2025-01-31',
            ])
        );

        $response->assertOk()
            ->assertJsonPath('data.revenue', 125.50)
            ->assertJsonPath('data.period.from', '2025-01-01')
            ->assertJsonPath('data.period.to', '2025-01-31');
    }

    /** @test */
    public function revenue_endpoint_requires_valid_dates(): void
    {
        $this->signInAdmin();

        $this->getJson(
            route('admin.api.billing.revenue', [
                'from' => '2025-01-10',
                'to' => '2025-01-05',
            ])
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }
}


<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmPaymentInstallment;

class GetUserRentalData extends Command
{
    protected $signature = 'test:get-rental-data {user_id=1037}';
    protected $description = 'Get rental data for testing collect-payment';

    public function handle()
    {
        $userId = $this->argument('user_id');

        $this->info("=== Fetching Rental Data for User ID: {$userId} ===\n");

        $rentals = RmRental::where('user_id', $userId)
            ->with(['activeContract', 'property'])
            ->get();

        if ($rentals->isEmpty()) {
            $this->error("No rentals found for user {$userId}");
            return 1;
        }

        $this->info("Found {$rentals->count()} rental(s)\n");

        foreach ($rentals as $rental) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("RENTAL ID: {$rental->id}");
            $this->line("Tenant: {$rental->tenant_full_name}");
            $this->line("Status: {$rental->status}");
            $this->line("Total Amount: {$rental->total_rental_amount} {$rental->currency}");

            if ($rental->activeContract) {
                $this->line("Active Contract ID: {$rental->activeContract->id}");
                $this->line("Contract Period: {$rental->activeContract->start_date} to {$rental->activeContract->end_date}");
            } else {
                $this->warn("No active contract");
            }

            // Get installments
            $installments = RmPaymentInstallment::where('rental_id', $rental->id)
                ->orderBy('due_date')
                ->get();

            if ($installments->isNotEmpty()) {
                $this->newLine();
                $this->line("Installments ({$installments->count()} total):");

                $headers = ['ID', 'Seq', 'Due Date', 'Amount', 'Paid', 'Remaining', 'Status'];
                $rows = [];

                foreach ($installments as $inst) {
                    $remaining = $inst->amount - ($inst->paid_amount ?? 0);
                    $rows[] = [
                        $inst->id,
                        $inst->sequence_no,
                        $inst->due_date,
                        number_format($inst->amount, 2),
                        number_format($inst->paid_amount ?? 0, 2),
                        number_format($remaining, 2),
                        $inst->status
                    ];
                }

                $this->table($headers, $rows);

                // Sample request
                $unpaidInstallment = $installments->where('status', '!=', 'paid')->first();
                if ($unpaidInstallment) {
                    $this->newLine();
                    $this->info("📋 SAMPLE API REQUEST:");
                    $this->line("POST /api/v1/rms/rentals/{$rental->id}/collect-payment");
                    $this->newLine();

                    $sampleRequest = [
                        'payments' => [
                            [
                                'installment_id' => $unpaidInstallment->id,
                                'payment_type' => 'rent',
                                'amount' => floatval($unpaidInstallment->amount - ($unpaidInstallment->paid_amount ?? 0)),
                                'notes' => "Payment for installment {$unpaidInstallment->sequence_no}"
                            ]
                        ],
                        'payment_method' => 'bank_transfer',
                        'payment_date' => date('Y-m-d'),
                        'bank_name' => 'البنك الأهلي السعودي',
                        'transfer_to' => 'منصة ناجز',
                        'reference' => 'TXN-' . time(),
                        'notes' => 'Test payment'
                    ];

                    $this->line(json_encode($sampleRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            } else {
                $this->warn("\nNo installments found for this rental");
            }

            $this->newLine();
        }

        $this->info("✅ Data retrieval complete!");
        return 0;
    }
}


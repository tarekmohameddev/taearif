<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Api\Rms\RmRental;
use App\Services\Rms\RentalService;
use Illuminate\Support\Facades\DB;

class TestRenewRental extends Command
{
    protected $signature = 'test:renew-rental {userId=1037}';
    protected $description = 'Test the rental renewal endpoint';

    public function handle()
    {
        $userId = $this->argument('userId');

        $this->info("=== Testing Rental Renewal for User {$userId} ===\n");

        // Find rentals
        $this->info("Step 1: Finding rentals...");
        $rentals = RmRental::where('user_id', $userId)->get();

        if ($rentals->isEmpty()) {
            $this->error("No rentals found for user {$userId}");
            return 1;
        }

        $this->info("Total rentals found: " . $rentals->count());

        $this->table(
            ['ID', 'Status', 'Tenant', 'Move-in Date', 'End Date'],
            $rentals->map(fn($r) => [
                $r->id,
                $r->status,
                $r->tenant_full_name ?? 'N/A',
                $r->move_in_date ?? 'N/A',
                $r->end_date ?? 'N/A'
            ])
        );

        // Find ended rental
        $endedRental = $rentals->where('status', 'ended')->first();

        if (!$endedRental) {
            $this->warn("No 'ended' rentals found. Creating a test scenario...");

            $activeRental = $rentals->where('status', 'active')->first();

            if ($activeRental) {
                $activeRental->update([
                    'status' => 'ended',
                    'end_date' => now()->subDays(1)->toDateString()
                ]);

                $this->info("✅ Rental {$activeRental->id} is now ended");
                $endedRental = $activeRental->fresh();
            } else {
                $this->error("No active rentals to end");
                return 1;
            }
        }

        // Check if the ended rental's unit has an active rental
        if ($endedRental->unit_id) {
            $conflictingRental = $rentals->where('unit_id', $endedRental->unit_id)
                ->where('status', 'active')
                ->first();

            if ($conflictingRental) {
                $this->warn("\n⚠️  Unit conflict detected!");
                $this->warn("Rental {$endedRental->id} (ended) shares the same unit with Rental {$conflictingRental->id} (active)");
                $this->warn("Tenant: {$conflictingRental->tenant_full_name}");
                $this->warn("This means the unit is currently rented.");
                $this->info("\nFor testing purposes, temporarily clearing the ended rental's unit_id...");

                // Temporarily clear unit_id for testing
                $endedRental->update(['unit_id' => null]);
                $this->info("Unit_id cleared. Renewal will proceed without unit assignment.");
            }
        }

        $this->info("\nStep 2: Testing renewal for Rental ID: {$endedRental->id}");
        $this->info("Tenant: {$endedRental->tenant_full_name}");

        // Test data
        $renewalData = [
            'rental_type' => 'monthly',
            'rental_duration' => 12,
            'paying_plan' => 'monthly',
            'total_rental_amount' => 60000,
            'currency' => 'SAR',
            'notes' => 'Test renewal from command',
            'cost_items' => []
        ];

        $this->info("\nRenewal data:");
        $this->line(json_encode($renewalData, JSON_PRETTY_PRINT));

        $this->info("\nProceeding with renewal...");

        try {
            $rentalService = app(RentalService::class);
            $result = $rentalService->renewRental($userId, $endedRental->id, $renewalData);

            $this->info("\n✅ Renewal successful!");
            $this->info("New Rental ID: " . $result['id']);
            $this->info("Old Rental ID: " . $result['old_rental_id']);
            $this->info("Status: " . $result['status']);

            if (isset($result['contract'])) {
                $this->info("\nContract Details:");
                $this->info("Contract ID: " . $result['contract']['id']);
                $this->info("Start Date: " . $result['contract']['start_date']);
                $this->info("End Date: " . $result['contract']['end_date']);
            }

            // Show the new rental details
            $newRental = RmRental::with(['activeContract'])->find($result['id']);
            if ($newRental) {
                $this->info("\n📋 New Rental Details:");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Rental ID', $newRental->id],
                        ['Tenant', $newRental->tenant_full_name],
                        ['Status', $newRental->status],
                        ['Move-in Date', $newRental->move_in_date],
                        ['Total Amount', number_format($newRental->total_rental_amount, 2)],
                        ['Currency', $newRental->currency],
                    ]
                );
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("\n❌ Renewal failed!");
            $this->error("Error: " . $e->getMessage());
            $this->error("\nStack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}

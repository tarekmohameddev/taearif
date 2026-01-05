<?php

namespace App\Console\Commands;

use App\Models\ApiCustomer;
use App\Models\Api\UserPropertyRequest;
use App\Support\PhoneNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkPropertyRequestsToCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'property-requests:link-customers 
                            {--dry-run : Show what would be linked without making changes}
                            {--tenant= : Only process requests for a specific tenant (user_id)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link existing property requests to existing customers by phone number (one-time data fix)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');

        $this->info('Starting to link property requests to customers by phone number...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get property requests that don't have linked customers
        $query = UserPropertyRequest::whereNotNull('phone')
            ->whereDoesntHave('customer');

        if ($tenantId) {
            $query->where('user_id', $tenantId);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No property requests found to process.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} property request(s) to process.");
        $this->newLine();

        $linked = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Process in chunks for better performance
        $query->chunkById(500, function ($propertyRequests) use (&$linked, &$skipped, &$failed, $dryRun, $bar) {
            foreach ($propertyRequests as $propertyRequest) {
                try {
                    // Normalize phone number
                    $normalizedPhone = PhoneNormalizer::normalize($propertyRequest->phone);

                    if (!$normalizedPhone) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    // Find customer with matching phone number in the same tenant
                    $customer = ApiCustomer::where('user_id', $propertyRequest->user_id)
                        ->where('phone_number', $normalizedPhone)
                        ->first();

                    if (!$customer) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    // Only link if customer doesn't already have a property_request_id
                    if ($customer->property_request_id !== null) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    if (!$dryRun) {
                        DB::transaction(function () use ($customer, $propertyRequest) {
                            // Link the customer
                            $customer->property_request_id = $propertyRequest->id;

                            // Update source only if not already set (preserve original source)
                            if (!$customer->source || $customer->source === 'manual') {
                                $customer->source = 'property_request';
                                $customer->source_id = $propertyRequest->id;
                            }

                            // Update empty fields only (non-destructive)
                            if (!$customer->city_id && $propertyRequest->city_id) {
                                $customer->city_id = $propertyRequest->city_id;
                            }

                            if (!$customer->district_id && $propertyRequest->districts_id) {
                                $customer->district_id = $propertyRequest->districts_id;
                            }

                            // Append to notes (preserve history)
                            $linkNote = "\n\nتم ربط العميل بطلب عقار (#{$propertyRequest->id}) في " . now()->format('Y-m-d H:i');
                            $customer->note = ($customer->note ?? '') . $linkNote;

                            $customer->save();
                        });
                    }

                    $linked++;
                    $bar->advance();

                } catch (\Exception $e) {
                    $failed++;
                    $bar->advance();
                    $this->newLine();
                    $this->error("Failed to process property request #{$propertyRequest->id}: " . $e->getMessage());
                }
            }
        });

        $bar->finish();
        $this->newLine();
        $this->newLine();

        // Display results
        $this->info('Completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Linked', $linked],
                ['Skipped', $skipped],
                ['Failed', $failed],
                ['Total Processed', $total],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn("This was a dry run. Run without --dry-run to apply changes.");
        }

        return self::SUCCESS;
    }
}


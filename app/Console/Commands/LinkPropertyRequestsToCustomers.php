<?php

namespace App\Console\Commands;

use App\Models\ApiCustomer;
use App\Models\Api\UserPropertyRequest;
use App\Services\PropertyRequestCustomerService;
use App\Support\PhoneNormalizer;
use Illuminate\Console\Command;

class LinkPropertyRequestsToCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'property-requests:link-customers 
                            {--dry-run : Show what would be linked/created without making changes}
                            {--tenant= : Only process requests for a specific tenant (user_id)}
                            {--link-only : Only link to existing customers, do not create new ones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link existing property requests to existing customers by phone, or create customers for unmatched requests (one-time data fix)';

    public function __construct(
        private PropertyRequestCustomerService $customerService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');
        $linkOnly = (bool) $this->option('link-only');

        $this->info('Starting to link property requests to customers (and create if needed)...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($linkOnly) {
            $this->info('LINK-ONLY MODE - Will only link to existing customers');
            $this->newLine();
        }

        // Get property requests that don't have linked customers
        $query = UserPropertyRequest::whereNotNull('phone')
            ->whereDoesntHave('customers');

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
        $created = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Process in chunks for better performance
        $query->chunkById(500, function ($propertyRequests) use (&$linked, &$created, &$skipped, &$failed, $dryRun, $linkOnly, $bar) {
            foreach ($propertyRequests as $propertyRequest) {
                try {
                    // Normalize phone number
                    $normalizedPhone = PhoneNormalizer::normalize($propertyRequest->phone);

                    if (!$normalizedPhone) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    if ($linkOnly) {
                        // Only link mode: find existing customer
                        $customer = ApiCustomer::where('user_id', $propertyRequest->user_id)
                            ->where('phone_number', $normalizedPhone)
                            ->whereDoesntHave('propertyRequests')
                            ->first();

                        if ($customer) {
                            if (!$dryRun) {
                                $this->customerService->linkExistingCustomer($customer, $propertyRequest);
                            }
                            $linked++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        // Combined mode: link or create
                        // Check if customer exists before calling service method for accurate tracking
                        $existingCustomer = ApiCustomer::where('user_id', $propertyRequest->user_id)
                            ->where('phone_number', $normalizedPhone)
                            ->first();

                        if (!$dryRun) {
                            $customer = $this->customerService->linkOrCreateCustomer($propertyRequest);

                            if ($customer) {
                                // If customer existed before and was not linked, it was linked
                                // If customer didn't exist, it was created
                                if ($existingCustomer && !$existingCustomer->propertyRequests()->exists()) {
                                    $linked++;
                                } elseif (!$existingCustomer) {
                                    $created++;
                                } else {
                                    // Customer existed but was already linked (shouldn't happen, but handle it)
                                    $skipped++;
                                }
                            } else {
                                $skipped++;
                            }
                        } else {
                            // Dry run: just check what would happen
                            if ($existingCustomer) {
                                if (!$existingCustomer->propertyRequests()->exists()) {
                                    $linked++;
                                } else {
                                    $skipped++;
                                }
                            } else {
                                // Would create new customer (if stage available)
                                // For dry run, we'll count as "created" potential
                                // But we can't check stage availability easily here, so count as created
                                $created++;
                            }
                        }
                    }

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
        $rows = [
            ['Linked', $linked],
            ['Created', $created],
            ['Skipped', $skipped],
            ['Failed', $failed],
            ['Total Processed', $total],
        ];

        $this->table(
            ['Status', 'Count'],
            $rows
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn("This was a dry run. Run without --dry-run to apply changes.");
        }

        return self::SUCCESS;
    }
}


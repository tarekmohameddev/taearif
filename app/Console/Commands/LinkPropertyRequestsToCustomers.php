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
                            {--link-only : Only link to existing customers, do not create new ones}
                            {--detailed : Show detailed information about each request processed}
                            {--show-tenants : List all tenants with unlinked property requests}';

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
        $detailed = (bool) $this->option('detailed');
        $showTenants = (bool) $this->option('show-tenants');

        // Show tenants list if requested
        if ($showTenants) {
            return $this->showTenantsWithUnlinkedRequests();
        }

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

        if ($detailed) {
            $this->info('DETAILED MODE - Showing detailed information');
            $this->newLine();
        }

        // Get property requests that don't have linked customers
        $query = UserPropertyRequest::whereNotNull('phone')
            ->whereNull('customer_id');

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
        $skipReasons = [];

        $bar = !$detailed ? $this->output->createProgressBar($total) : null;
        $bar?->start();

        // Process in chunks for better performance
        $query->chunkById(500, function ($propertyRequests) use (&$linked, &$created, &$skipped, &$failed, &$skipReasons, $dryRun, $linkOnly, $detailed, $bar) {
            foreach ($propertyRequests as $propertyRequest) {
                try {
                    if ($detailed) {
                        $tenant = $propertyRequest->user;
                        $this->line("Processing PR #{$propertyRequest->id} | Tenant: {$tenant->name} (ID: {$tenant->id}, Email: {$tenant->email}) | Phone: {$propertyRequest->phone}");
                    }

                    // Normalize phone number
                    $normalizedPhone = PhoneNormalizer::normalize($propertyRequest->phone);

                    if (!$normalizedPhone) {
                        $reason = "Cannot normalize phone: {$propertyRequest->phone}";
                        $skipReasons[] = "PR #{$propertyRequest->id}: {$reason}";
                        if ($detailed) {
                            $this->warn("  ⏭️  Skipped: {$reason}");
                        }
                        $skipped++;
                        $bar?->advance();
                        continue;
                    }

                    if ($detailed) {
                        $this->line("  Normalized phone: {$normalizedPhone}");
                    }

                    if ($linkOnly) {
                        // Only link mode: find existing customer without any property requests
                        $customer = ApiCustomer::where('user_id', $propertyRequest->user_id)
                            ->where('phone_number', $normalizedPhone)
                            ->whereDoesntHave('propertyRequests')
                            ->first();

                        if ($customer) {
                            if (!$dryRun) {
                                $this->customerService->linkExistingCustomer($customer, $propertyRequest);
                            }
                            if ($detailed) {
                                $this->info("  ✅ Linked to existing customer #{$customer->id}");
                            }
                            $linked++;
                        } else {
                            $reason = "No existing customer found with phone {$normalizedPhone}";
                            $skipReasons[] = "PR #{$propertyRequest->id}: {$reason}";
                            if ($detailed) {
                                $this->warn("  ⏭️  Skipped: {$reason}");
                            }
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
                                $hadNoRequests = $existingCustomer ? $existingCustomer->propertyRequests()->where('id', '!=', $propertyRequest->id)->doesntExist() : false;
                                if ($existingCustomer && $hadNoRequests) {
                                    if ($detailed) {
                                        $this->info("  ✅ Linked to existing customer #{$customer->id}");
                                    }
                                    $linked++;
                                } elseif (!$existingCustomer) {
                                    if ($detailed) {
                                        $this->info("  ✨ Created new customer #{$customer->id}");
                                    }
                                    $created++;
                                } else {
                                    // Customer existed but was already linked (shouldn't happen, but handle it)
                                    $reason = "Customer already linked to other requests";
                                    $skipReasons[] = "PR #{$propertyRequest->id}: {$reason}";
                                    if ($detailed) {
                                        $this->warn("  ⏭️  Skipped: {$reason}");
                                    }
                                    $skipped++;
                                }
                            } else {
                                $reason = "Service returned null (check logs for details - likely missing stage or phone normalization issue)";
                                $skipReasons[] = "PR #{$propertyRequest->id}: {$reason}";
                                if ($detailed) {
                                    $this->warn("  ⏭️  Skipped: {$reason}");
                                }
                                $skipped++;
                            }
                        } else {
                            // Dry run: just check what would happen
                            if ($existingCustomer) {
                                if ($existingCustomer->propertyRequests()->doesntExist()) {
                                    if ($detailed) {
                                        $this->info("  ✅ Would link to existing customer #{$existingCustomer->id}");
                                    }
                                    $linked++;
                                } else {
                                    $reason = "Customer already linked to other requests";
                                    $skipReasons[] = "PR #{$propertyRequest->id}: {$reason}";
                                    if ($detailed) {
                                        $this->warn("  ⏭️  Would skip: {$reason}");
                                    }
                                    $skipped++;
                                }
                            } else {
                                // Would create new customer (if stage available)
                                if ($detailed) {
                                    $this->info("  ✨ Would create new customer");
                                }
                                $created++;
                            }
                        }
                    }

                    $bar?->advance();

                } catch (\Exception $e) {
                    $failed++;
                    $skipReasons[] = "PR #{$propertyRequest->id}: Exception - {$e->getMessage()}";
                    $bar?->advance();
                    if (!$detailed) {
                        $this->newLine();
                    }
                    $this->error("  ❌ Failed to process property request #{$propertyRequest->id}: " . $e->getMessage());
                }
            }
        });

        $bar?->finish();
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

        // Show skip reasons if any
        if ($skipped > 0 && !empty($skipReasons) && !$detailed) {
            $this->newLine();
            $this->warn("Skipped requests details:");
            foreach (array_slice($skipReasons, 0, 10) as $reason) {
                $this->line("  • {$reason}");
            }
            if (count($skipReasons) > 10) {
                $this->line("  ... and " . (count($skipReasons) - 10) . " more. Use --detailed to see all.");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn("This was a dry run. Run without --dry-run to apply changes.");
        }

        return self::SUCCESS;
    }

    /**
     * Show all tenants with unlinked property requests
     */
    private function showTenantsWithUnlinkedRequests(): int
    {
        $this->info('Tenants with unlinked property requests:');
        $this->newLine();

        $tenants = \App\Models\User::where('account_type', 'tenant')
            ->whereHas('propertyRequests', function ($q) {
                $q->whereNull('customer_id')->whereNotNull('phone');
            })
            ->withCount(['propertyRequests' => function ($q) {
                $q->whereNull('customer_id')->whereNotNull('phone');
            }])
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found with unlinked property requests.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($tenants as $tenant) {
            $rows[] = [
                $tenant->id,
                $tenant->name ?? 'N/A',
                $tenant->email ?? 'N/A',
                $tenant->property_requests_count,
            ];
        }

        $this->table(
            ['Tenant ID', 'Name', 'Email', 'Unlinked Requests'],
            $rows
        );

        $this->newLine();
        $this->info("To process a specific tenant, use: php artisan property-requests:link-customers --tenant=<ID>");
        $this->info("Example: php artisan property-requests:link-customers --tenant={$tenants->first()->id}");

        return self::SUCCESS;
    }
}


<?php

namespace App\Console\Commands;

use App\Models\Api\UserPropertyRequest;
use App\Models\ApiCustomer;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Support\PhoneNormalizer;
use Illuminate\Console\Command;

class DiagnosePropertyRequestSkipping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'property-requests:diagnose-skipping 
                            {--tenant= : Only check requests for a specific tenant (user_id)}
                            {--limit=50 : Limit the number of requests to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose why property requests are being skipped when linking to customers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $limit = (int) $this->option('limit');

        $this->info('Diagnosing why property requests are being skipped...');
        $this->newLine();

        // Get property requests that don't have linked customers
        $query = UserPropertyRequest::whereNotNull('phone')
            ->whereDoesntHave('customer');

        if ($tenantId) {
            $query->where('user_id', $tenantId);
        }

        $propertyRequests = $query->limit($limit)->get();

        if ($propertyRequests->isEmpty()) {
            $this->info('No property requests found to diagnose.');
            return self::SUCCESS;
        }

        $this->info("Found {$propertyRequests->count()} property request(s) to diagnose.");
        $this->newLine();

        $reasons = [
            'phone_normalization_failed' => [],
            'customer_exists_linked' => [],
            'customer_exists_unlinked' => [],
            'no_stage_available' => [],
            'would_create' => [],
        ];

        foreach ($propertyRequests as $propertyRequest) {
            $diagnosis = $this->diagnoseRequest($propertyRequest);
            $reasons[$diagnosis['reason']][] = $diagnosis;
        }

        // Display results
        $this->displayResults($reasons);

        return self::SUCCESS;
    }

    /**
     * Diagnose a single property request
     */
    private function diagnoseRequest(UserPropertyRequest $propertyRequest): array
    {
        $result = [
            'id' => $propertyRequest->id,
            'tenant_id' => $propertyRequest->user_id,
            'phone' => $propertyRequest->phone,
            'reason' => '',
            'details' => '',
        ];

        // Check phone normalization
        $normalizedPhone = PhoneNormalizer::normalize($propertyRequest->phone);
        if (!$normalizedPhone) {
            $result['reason'] = 'phone_normalization_failed';
            $result['details'] = "Phone '{$propertyRequest->phone}' could not be normalized";
            return $result;
        }

        $result['normalized_phone'] = $normalizedPhone;

        // Check if customer exists
        $existingCustomer = ApiCustomer::where('user_id', $propertyRequest->user_id)
            ->where('phone_number', $normalizedPhone)
            ->first();

        if ($existingCustomer) {
            if ($existingCustomer->property_request_id) {
                $result['reason'] = 'customer_exists_linked';
                $result['details'] = "Customer #{$existingCustomer->id} already linked to property request #{$existingCustomer->property_request_id}";
            } else {
                $result['reason'] = 'customer_exists_unlinked';
                $result['details'] = "Customer #{$existingCustomer->id} exists but is not linked (should be linkable)";
            }
            return $result;
        }

        // Check stage availability
        $settings = PropertyRequestAutoCustomerSetting::where('user_id', $propertyRequest->user_id)->first();
        $defaultStageId = UserApiCustomerStage::where('user_id', $propertyRequest->user_id)
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->value('id');

        $stageId = $settings ? $settings->default_stage_id : $defaultStageId;

        if (!$stageId) {
            $result['reason'] = 'no_stage_available';
            $result['details'] = "No active stage found for tenant (settings: " . ($settings ? 'exists' : 'none') . ", default stage: " . ($defaultStageId ?: 'none') . ")";
            return $result;
        }

        // Would create
        $result['reason'] = 'would_create';
        $result['details'] = "Would create new customer with stage ID: {$stageId}";
        return $result;
    }

    /**
     * Display diagnosis results
     */
    private function displayResults(array $reasons): void
    {
        $total = array_sum(array_map('count', $reasons));

        $this->info("Diagnosis Summary:");
        $this->line("Total requests analyzed: {$total}");
        $this->newLine();

        // Phone normalization failures
        if (!empty($reasons['phone_normalization_failed'])) {
            $this->warn("❌ Phone Normalization Failed: " . count($reasons['phone_normalization_failed']));
            $this->table(
                ['Request ID', 'Tenant ID', 'Phone', 'Details'],
                array_map(function ($r) {
                    return [$r['id'], $r['tenant_id'], $r['phone'], $r['details']];
                }, $reasons['phone_normalization_failed'])
            );
            $this->newLine();
        }

        // Customers already linked
        if (!empty($reasons['customer_exists_linked'])) {
            $this->warn("⚠️  Customer Already Linked: " . count($reasons['customer_exists_linked']));
            $this->table(
                ['Request ID', 'Tenant ID', 'Normalized Phone', 'Details'],
                array_map(function ($r) {
                    return [$r['id'], $r['tenant_id'], $r['normalized_phone'] ?? 'N/A', $r['details']];
                }, $reasons['customer_exists_linked'])
            );
            $this->newLine();
        }

        // Customers exist but unlinked (should be linkable)
        if (!empty($reasons['customer_exists_unlinked'])) {
            $this->info("✅ Customer Exists (Unlinked - Should Link): " . count($reasons['customer_exists_unlinked']));
            $this->table(
                ['Request ID', 'Tenant ID', 'Normalized Phone', 'Details'],
                array_map(function ($r) {
                    return [$r['id'], $r['tenant_id'], $r['normalized_phone'] ?? 'N/A', $r['details']];
                }, $reasons['customer_exists_unlinked'])
            );
            $this->newLine();
        }

        // No stage available
        if (!empty($reasons['no_stage_available'])) {
            $this->error("❌ No Stage Available: " . count($reasons['no_stage_available']));
            $this->table(
                ['Request ID', 'Tenant ID', 'Normalized Phone', 'Details'],
                array_map(function ($r) {
                    return [$r['id'], $r['tenant_id'], $r['normalized_phone'] ?? 'N/A', $r['details']];
                }, $reasons['no_stage_available'])
            );
            $this->newLine();
        }

        // Would create
        if (!empty($reasons['would_create'])) {
            $this->info("✅ Would Create Customer: " . count($reasons['would_create']));
            $this->table(
                ['Request ID', 'Tenant ID', 'Normalized Phone', 'Details'],
                array_map(function ($r) {
                    return [$r['id'], $r['tenant_id'], $r['normalized_phone'] ?? 'N/A', $r['details']];
                }, $reasons['would_create'])
            );
            $this->newLine();
        }

        // Summary
        $this->info("Summary:");
        $this->line("  • Phone normalization failed: " . count($reasons['phone_normalization_failed']));
        $this->line("  • Customer already linked: " . count($reasons['customer_exists_linked']));
        $this->line("  • Customer exists (unlinked): " . count($reasons['customer_exists_unlinked']));
        $this->line("  • No stage available: " . count($reasons['no_stage_available']));
        $this->line("  • Would create: " . count($reasons['would_create']));
    }
}


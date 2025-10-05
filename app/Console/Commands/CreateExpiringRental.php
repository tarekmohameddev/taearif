<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Rms\RentalService;
use App\Services\Rms\PaymentService;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateExpiringRental extends Command
{
    protected $signature = 'rental:create-expiring 
                            {user_id : The user ID to create rental for}
                            {--expired : Create rental with already expired contract}
                            {--7days : Create rental with contract expiring in 7 days}
                            {--14days : Create rental with contract expiring in 14 days}
                            {--30days : Create rental with contract expiring in 30 days}';
    
    protected $description = 'Create a rental with an expiring contract for testing. Use one of the expiration options.';

    protected $rentalService;

    public function __construct(RentalService $rentalService)
    {
        parent::__construct();
        $this->rentalService = $rentalService;
    }

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        // Determine expiration type
        $expirationType = $this->getExpirationType();
        
        if (!$expirationType) {
            $this->error('❌ Please specify one expiration option: --expired, --7days, --14days, or --30days');
            return 1;
        }

        $this->info("Creating rental with {$expirationType} contract for user ID: $userId");
        $this->newLine();

        try {
            DB::beginTransaction();
            
            // Generate rental data
            $rentalData = $this->generateRentalData($userId, $expirationType);
            
            // Create rental
            $rental = RmRental::create($rentalData);
            $this->info("✅ Rental created with ID: {$rental->id}");
            
            // Create contract
            $contract = $this->createContract($userId, $rental, $expirationType);
            $this->info("✅ Contract created with ID: {$contract->id}");
            
            // Create installments
            $this->createInstallments($userId, $rental, $contract);
            $this->info("✅ Created {$rental->rental_period} installments");
            
            DB::commit();
            
            // Display summary
            $this->displaySummary($rental, $contract, $expirationType);
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->error("❌ Error creating rental: " . $e->getMessage());
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    private function getExpirationType()
    {
        if ($this->option('expired')) return 'expired';
        if ($this->option('7days')) return '7days';
        if ($this->option('14days')) return '14days';
        if ($this->option('30days')) return '30days';
        return null;
    }

    private function generateRentalData($userId, $expirationType)
    {
        // Generate random tenant data
        $tenantNames = [
            'Ahmed Al-Rashid', 'Fatima Al-Zahra', 'Mohammed Al-Sayed', 'Aisha Al-Mansouri',
            'Omar Al-Hassan', 'Khadija Al-Mahmoud', 'Yusuf Al-Nasser', 'Zainab Al-Farouk',
            'Hassan Al-Mustafa', 'Mariam Al-Qasim'
        ];

        $tenantPhones = [
            '+966501234567', '+966502345678', '+966503456789', '+966504567890',
            '+966505678901', '+966506789012', '+966507890123', '+966508901234'
        ];

        $tenantEmails = [
            'ahmed.rashid@email.com', 'fatima.zahra@email.com', 'mohammed.sayed@email.com',
            'aisha.mansouri@email.com', 'omar.hassan@email.com', 'khadija.mahmoud@email.com',
            'yusuf.nasser@email.com', 'zainab.farouk@email.com', 'hassan.mustafa@email.com',
            'mariam.qasim@email.com'
        ];

        // Random selection
        $tenantName = $tenantNames[array_rand($tenantNames)];
        $tenantPhone = $tenantPhones[array_rand($tenantPhones)];
        $tenantEmail = $tenantEmails[array_rand($tenantEmails)];

        // Calculate move-in date based on expiration type
        $moveInDate = $this->calculateMoveInDate($expirationType);
        $rentalPeriod = 12; // 12 payment periods
        $payingPlan = 'monthly'; // Monthly payments
        $baseRentAmount = rand(2000, 5000); // Random rent between 2000-5000 SAR
        $currency = 'SAR';

        // Random fees
        $platformFee = rand(50, 150);
        $waterFee = rand(100, 300);
        $officeCommissionType = ['percentage', 'amount'][array_rand(['percentage', 'amount'])];
        $officeCommissionValue = $officeCommissionType === 'percentage' ? rand(2, 8) : rand(200, 800);
        $depositAmount = rand(1000, 3000);

        // Other random data
        $tenantJobTitles = ['Engineer', 'Teacher', 'Doctor', 'Manager', 'Accountant', 'Designer', 'Developer', 'Consultant'];
        $tenantSocialStatuses = ['single', 'married', 'divorced', 'widowed'];
        $unitLabels = ['A-101', 'B-205', 'C-301', 'D-102', 'E-403', 'F-201', 'G-304', 'H-105'];
        $propertyNumbers = ['P001', 'P002', 'P003', 'P004', 'P005', 'P006', 'P007', 'P008'];

        $tenantJobTitle = $tenantJobTitles[array_rand($tenantJobTitles)];
        $tenantSocialStatus = $tenantSocialStatuses[array_rand($tenantSocialStatuses)];
        $unitLabel = $unitLabels[array_rand($unitLabels)];
        $propertyNumber = $propertyNumbers[array_rand($propertyNumbers)];

        return [
            'user_id' => $userId,
            'tenant_full_name' => $tenantName,
            'tenant_phone' => $tenantPhone,
            'tenant_email' => $tenantEmail,
            'tenant_job_title' => $tenantJobTitle,
            'tenant_social_status' => $tenantSocialStatus,
            'tenant_national_id' => '1' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            'property_id' => rand(1, 10),
            'project_id' => rand(1, 5),
            'property_number' => $propertyNumber,
            'move_in_date' => $moveInDate,
            'rental_period' => $rentalPeriod,
            'paying_plan' => $payingPlan,
            'base_rent_amount' => $baseRentAmount,
            'currency' => $currency,
            'deposit_amount' => $depositAmount,
            'platform_fee' => $platformFee,
            'water_fee' => $waterFee,
            'office_commission_type' => $officeCommissionType,
            'office_commission_value' => $officeCommissionValue,
            'contract_number' => 'CNT-' . date('Y') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'active',
            'notes' => "Test rental with {$expirationType} contract - created via artisan command",
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }

    private function calculateMoveInDate($expirationType)
    {
        $now = Carbon::now();
        
        switch ($expirationType) {
            case 'expired':
                // Contract expired 1 month ago (started 13 months ago)
                return $now->copy()->subMonths(13)->format('Y-m-d');
            case '7days':
                // Contract expires in 7 days (started 11 months and 23 days ago)
                return $now->copy()->subMonths(11)->subDays(23)->format('Y-m-d');
            case '14days':
                // Contract expires in 14 days (started 11 months and 16 days ago)
                return $now->copy()->subMonths(11)->subDays(16)->format('Y-m-d');
            case '30days':
                // Contract expires in 30 days (started 11 months ago)
                return $now->copy()->subMonths(11)->format('Y-m-d');
            default:
                return $now->copy()->subMonths(11)->format('Y-m-d');
        }
    }

    private function createContract($userId, $rental, $expirationType)
    {
        $totalMonths = $rental->rental_period;
        $contractEndDate = Carbon::parse($rental->move_in_date)->addMonths($totalMonths)->subDay();
        
        return RmContract::create([
            'user_id' => $userId,
            'rental_id' => $rental->id,
            'start_date' => $rental->move_in_date,
            'end_date' => $contractEndDate,
            'status' => 'active',
            'property_id' => $rental->property_id,
            'project_id' => $rental->project_id,
            'property_name' => 'Test Property ' . $rental->property_id,
            'project_name' => 'Test Project ' . $rental->project_id,
            'grace_period_months' => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function createInstallments($userId, $rental, $contract)
    {
        $chunks = 1; // Monthly
        $installmentAmount = round($rental->base_rent_amount * $chunks, 2);
        $start = Carbon::parse($rental->move_in_date);
        
        for ($i = 0; $i < $rental->rental_period; $i++) {
            RmPaymentInstallment::create([
                'user_id' => $userId,
                'rental_id' => $rental->id,
                'contract_id' => $contract->id,
                'sequence_no' => $i + 1,
                'due_date' => $start->copy()->addMonths($i * $chunks),
                'amount' => $installmentAmount,
                'status' => 'pending',
                'payment_type' => 'none',
                'payment_status' => 'not_due',
            ]);
        }
    }

    private function displaySummary($rental, $contract, $expirationType)
    {
        $daysUntilExpiration = now()->diffInDays($contract->end_date);
        $expirationStatus = $daysUntilExpiration < 0 ? 'EXPIRED' : 'EXPIRES IN ' . $daysUntilExpiration . ' DAYS';
        
        $this->newLine();
        $this->info("🎉 SUCCESS! Rental with {$expirationType} contract created successfully!");
        $this->newLine();
        $this->info("📋 Summary:");
        $this->line("- Rental ID: {$rental->id}");
        $this->line("- Contract ID: {$contract->id}");
        $this->line("- Tenant: {$rental->tenant_full_name}");
        $this->line("- Phone: {$rental->tenant_phone}");
        $this->line("- Email: {$rental->tenant_email}");
        $this->line("- Job: {$rental->tenant_job_title}");
        $this->line("- Social Status: {$rental->tenant_social_status}");
        $this->line("- Property Number: {$rental->property_number}");
        $this->line("- Move-in Date: {$rental->move_in_date}");
        $this->line("- Contract End Date: {$contract->end_date}");
        $this->line("- Contract Status: {$expirationStatus}");
        $this->line("- Base Rent: {$rental->base_rent_amount} {$rental->currency}");
        $this->line("- Deposit: {$rental->deposit_amount} {$rental->currency}");
        $this->line("- Platform Fee: {$rental->platform_fee} {$rental->currency}");
        $this->line("- Water Fee: {$rental->water_fee} {$rental->currency}");
        $this->line("- Office Commission: {$rental->office_commission_value} " . ($rental->office_commission_type === 'percentage' ? '%' : $rental->currency));
        $this->line("- Contract Number: {$rental->contract_number}");
        $this->line("- Total Installments: {$rental->rental_period}");
        $this->line("- Monthly Installment Amount: " . round($rental->base_rent_amount, 2) . " {$rental->currency}");
        
        $this->newLine();
        $this->info("🔗 API Endpoints to test:");
        $this->line("GET /api/v1/rms/rentals/{$rental->id}");
        $this->line("GET /api/v1/rms/rentals/{$rental->id}/details-with-payments");
        $this->line("GET /api/v1/rms/rentals/{$rental->id}/current-collections");
    }
}

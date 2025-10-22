<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Constants\RmsConstants;
use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;

/**
 * Manual test command for RMS infrastructure
 *
 * Run with: php artisan test:rms-infrastructure
 */
class TestRmsInfrastructure extends Command
{
    protected $signature = 'test:rms-infrastructure';
    protected $description = 'Test RMS base infrastructure (BaseApiController, HandlesApiExceptions, RmsConstants)';

    public function handle()
    {
        $this->info('===================================================');
        $this->info('  Testing RMS Infrastructure Components');
        $this->info('===================================================');
        $this->newLine();

        $passed = 0;
        $failed = 0;

        // Test 1: RmsConstants exists and is loadable
        $this->info('[Test 1] Checking RmsConstants class...');
        if (class_exists(RmsConstants::class)) {
            $this->line('  ✓ RmsConstants class exists');
            $passed++;
        } else {
            $this->error('  ✗ RmsConstants class not found');
            $failed++;
            return 1;
        }

        // Test 2: Constants have correct values
        $this->info('[Test 2] Checking constant values...');
        $tests = [
            'RENTAL_TYPES' => 2,
            'PAYING_PLANS' => 4,
            'RENTAL_STATUSES' => 6,
            'PAYMENT_METHODS' => 6,
            'CONTRACT_STATUSES' => 4,
            'INSTALLMENT_STATUSES' => 5,
            'MAINTENANCE_PRIORITIES' => 4,
        ];

        foreach ($tests as $constant => $expectedCount) {
            $values = constant("App\\Constants\\RmsConstants::{$constant}");
            if (count($values) === $expectedCount) {
                $this->line("  ✓ {$constant}: {$expectedCount} values");
                $passed++;
            } else {
                $this->error("  ✗ {$constant}: Expected {$expectedCount}, got " . count($values));
                $failed++;
            }
        }

        // Test 3: Validation rule generation
        $this->info('[Test 3] Testing validation rule generation...');
        $rule = RmsConstants::validationRule(RmsConstants::RENTAL_TYPES);
        if ($rule === 'in:monthly,annual') {
            $this->line('  ✓ Validation rule generated correctly: ' . $rule);
            $passed++;
        } else {
            $this->error('  ✗ Validation rule incorrect: ' . $rule);
            $failed++;
        }

        // Test 4: Value validation
        $this->info('[Test 4] Testing value validation...');
        if (RmsConstants::isValid('active', RmsConstants::RENTAL_STATUSES)) {
            $this->line('  ✓ Valid value recognized');
            $passed++;
        } else {
            $this->error('  ✗ Valid value not recognized');
            $failed++;
        }

        if (!RmsConstants::isValid('invalid', RmsConstants::RENTAL_STATUSES)) {
            $this->line('  ✓ Invalid value rejected');
            $passed++;
        } else {
            $this->error('  ✗ Invalid value not rejected');
            $failed++;
        }

        // Test 5: toArray() method
        $this->info('[Test 5] Testing toArray() method...');
        $allConstants = RmsConstants::toArray();
        if (is_array($allConstants) && count($allConstants) > 10) {
            $this->line('  ✓ toArray() returns ' . count($allConstants) . ' categories');
            $passed++;
        } else {
            $this->error('  ✗ toArray() failed');
            $failed++;
        }

        // Test 6: BaseApiController exists
        $this->info('[Test 6] Checking BaseApiController...');
        if (class_exists(BaseApiController::class)) {
            $this->line('  ✓ BaseApiController class exists');
            $passed++;
        } else {
            $this->error('  ✗ BaseApiController class not found');
            $failed++;
        }

        // Test 7: HandlesApiExceptions trait exists
        $this->info('[Test 7] Checking HandlesApiExceptions trait...');
        if (trait_exists(HandlesApiExceptions::class)) {
            $this->line('  ✓ HandlesApiExceptions trait exists');
            $passed++;
        } else {
            $this->error('  ✗ HandlesApiExceptions trait not found');
            $failed++;
        }

        // Test 8: Trait methods exist
        $this->info('[Test 8] Checking trait methods...');
        $traitFile = file_get_contents(app_path('Traits/HandlesApiExceptions.php'));
        $requiredMethods = ['executeWithExceptionHandling', 'handleApiException'];
        $methodsExist = true;

        foreach ($requiredMethods as $method) {
            if (strpos($traitFile, "function {$method}") === false) {
                $this->error("  ✗ Method {$method} not found in trait");
                $methodsExist = false;
                $failed++;
            }
        }

        if ($methodsExist) {
            $this->line('  ✓ All required trait methods exist');
            $passed++;
        }

        // Test 9: Arabic constants
        $this->info('[Test 9] Checking Arabic constants...');
        if (in_array('منصة ناجز', RmsConstants::TRANSFER_TO_OPTIONS)) {
            $this->line('  ✓ Arabic constants loaded correctly');
            $passed++;
        } else {
            $this->error('  ✗ Arabic constants not loaded');
            $failed++;
        }

        // Test 10: Constants can be used in validation
        $this->info('[Test 10] Testing Laravel validation integration...');
        $validator = \Validator::make(
            ['status' => 'active'],
            ['status' => ['required', RmsConstants::validationRule(RmsConstants::RENTAL_STATUSES)]]
        );

        if ($validator->passes()) {
            $this->line('  ✓ Constants work with Laravel validator');
            $passed++;
        } else {
            $this->error('  ✗ Validation integration failed');
            $failed++;
        }

        // Summary
        $this->newLine();
        $this->info('===================================================');
        $this->info('  Test Results');
        $this->info('===================================================');
        $this->line('  Passed: ' . $this->formatCount($passed, 'green'));
        $this->line('  Failed: ' . $this->formatCount($failed, 'red'));
        $this->line('  Total:  ' . ($passed + $failed));
        $this->newLine();

        if ($failed === 0) {
            $this->info('✓ All tests passed! Infrastructure is ready to use.');
            $this->newLine();
            $this->info('Next steps:');
            $this->line('  1. Run unit tests: php artisan test --filter=Infrastructure');
            $this->line('  2. Update RmsDashboardController as proof of concept');
            $this->line('  3. Test the endpoint to ensure it works');
            return 0;
        } else {
            $this->error('✗ Some tests failed. Please review the errors above.');
            return 1;
        }
    }

    private function formatCount($count, $color)
    {
        return "<fg={$color}>{$count}</>";
    }
}


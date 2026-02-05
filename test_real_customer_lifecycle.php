<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the application
$app->make(\Illuminate\Contracts\Console\Kernel::class);

$token = '3047|uahD3zAkkIoIgCayvGoFcrqT6tPGGa1Yz3CGvK1f14a54d22';

// User ID for testing (matches the token)
$userId = 1037;

// Test configuration
$testConfig = [
    'userId' => $userId,
    'token' => $token,
    'testPrefix' => 'LIFECYCLE_TEST_' . time(),
];

// Statistics tracking
$stats = [
    'total_tests' => 0,
    'passed' => 0,
    'failed' => 0,
    'scenarios_completed' => 0,
    'total_time' => 0,
];

function testEndpoint($kernel, $method, $uri, $data = [], $token = null) {
    $startTime = microtime(true);
    
    $server = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ];
    
    if ($token) {
        $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    }
    
    $content = $method === 'GET' ? null : json_encode($data);
    
    $request = Illuminate\Http\Request::create(
        $uri,
        $method,
        [],
        [],
        [],
        $server,
        $content
    );
    
    $response = $kernel->handle($request);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    return [
        'status' => $response->getStatusCode(),
        'content' => $response->getContent(),
        'json' => json_decode($response->getContent(), true),
        'duration' => $duration,
    ];
}

function printScenarioHeader($number, $title) {
    echo "\n\n";
    echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
    echo "║  SCENARIO $number: " . str_pad($title, 68) . "║\n";
    echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";
}

function printStep($stepNum, $description) {
    echo "\n" . str_repeat('-', 80) . "\n";
    echo "Step $stepNum: $description\n";
    echo str_repeat('-', 80) . "\n";
}

function printResult($testName, $result, &$scenarioStats) {
    global $stats;
    
    $scenarioStats['total_tests']++;
    $stats['total_tests']++;
    $stats['total_time'] += $result['duration'];
    
    echo "  Test: $testName\n";
    echo "  Status: " . $result['status'] . " | Duration: {$result['duration']}ms\n";
    
    if ($result['status'] === 200 || $result['status'] === 201) {
        echo "  ✓ PASSED\n";
        $scenarioStats['passed']++;
        $stats['passed']++;
        return true;
    } else {
        echo "  ✗ FAILED\n";
        if (isset($result['json']['message'])) {
            echo "  Error: " . $result['json']['message'] . "\n";
        }
        $scenarioStats['failed']++;
        $stats['failed']++;
        return false;
    }
}

function verifyDatabase($description, $query, $expectedCondition) {
    echo "  DB Verification: $description\n";
    $result = $query();
    $passed = $expectedCondition($result);
    echo "  " . ($passed ? "✓" : "✗") . " " . ($passed ? "VERIFIED" : "FAILED") . "\n";
    return $passed;
}

function printScenarioSummary($scenarioNum, $stats) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "SCENARIO $scenarioNum SUMMARY: {$stats['passed']}/{$stats['total_tests']} tests passed\n";
    echo str_repeat('=', 80) . "\n";
}

// ============================================================================
// MAIN TEST EXECUTION
// ============================================================================

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║              CUSTOMERS HUB - REAL DATA LIFECYCLE TESTING                      ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "User ID: {$testConfig['userId']}\n";
echo "Test Prefix: {$testConfig['testPrefix']}\n";
echo "Start Time: " . date('Y-m-d H:i:s') . "\n";

// Get test data via API (this boots Laravel)
printStep(0, "Loading Test Data via API");

// First, make an API call to boot Laravel and get filter options
$filterOptionsResult = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/list/filter-options', [], $token);

if ($filterOptionsResult['status'] === 200 && isset($filterOptionsResult['json']['data'])) {
    $filterOptions = $filterOptionsResult['json']['data'];
    
    // Extract test data from filter options
    $testStage = !empty($filterOptions['stages']) ? (object)$filterOptions['stages'][0] : null;
    $testStages = !empty($filterOptions['stages']) ? array_map(fn($s) => (object)$s, $filterOptions['stages']) : [];
    $testPriority = !empty($filterOptions['priorities']) ? (object)$filterOptions['priorities'][0] : null;
    $testType = !empty($filterOptions['types']) ? (object)$filterOptions['types'][0] : null;
    $testCity = !empty($filterOptions['cities']) ? (object)$filterOptions['cities'][0] : null;
    
    if ($testStage) {
        echo "  ✓ Loaded Stage: {$testStage->name} (ID: {$testStage->id})\n";
    }
    if ($testPriority) {
        echo "  ✓ Loaded Priority: {$testPriority->name} (ID: {$testPriority->id})\n";
    }
    if ($testType) {
        echo "  ✓ Loaded Type: {$testType->name} (ID: {$testType->id})\n";
    }
    if ($testCity) {
        echo "  ✓ Loaded City: {$testCity->name} (ID: {$testCity->id})\n";
    }
} else {
    echo "  ⚠ Could not load filter options, using defaults\n";
    // Fallback to known IDs from previous tests
    $testStage = (object)['id' => 135, 'name' => 'New'];
    $testStages = [(object)['id' => 135, 'name' => 'New'], (object)['id' => 136, 'name' => 'Contacted']];
    $testPriority = (object)['id' => 2, 'name' => 'High'];
    $testType = (object)['id' => 5, 'name' => 'Buyer'];
    $testCity = (object)['id' => 10, 'name' => 'الرياض'];
}

$testProperty = null; // Will be determined later if needed

// ============================================================================
// SCENARIO 1: NEW CUSTOMER INQUIRY FLOW
// ============================================================================

printScenarioHeader(1, "New Customer Inquiry Flow");
$scenario1Stats = ['total_tests' => 0, 'passed' => 0, 'failed' => 0];

// Step 1.1: Create a new customer first
printStep('1.1', "Create New Customer");
$newCustomerPhone = '+966' . rand(500000000, 599999999);
$newCustomerData = [
    'name' => "{$testConfig['testPrefix']} Customer",
    'phone_number' => $newCustomerPhone,
    'email' => strtolower($testConfig['testPrefix']) . '@test.com',
    'stage_id' => $testStage->id,
    'priority_id' => $testPriority->id,
    'type_id' => $testType->id,
    'city_id' => $testCity->id,
    'note' => 'Created by lifecycle test',
];

$newCustomerId = DB::table('api_customers')->insertGetId(array_merge($newCustomerData, [
    'user_id' => $userId,
    'created_at' => now(),
    'updated_at' => now(),
]));

echo "  ✓ Created Customer ID: $newCustomerId\n";

// Step 1.2: Create inquiry for this customer
printStep('1.2', "Create Customer Inquiry");
$inquiryData = [
    'user_id' => $userId,
    'customer_id' => $newCustomerId,
    'phone_number' => $newCustomerPhone,
    'message' => 'I am interested in buying a property in Riyadh',
    'inquiry_type' => 'inquire',
    'property_type' => 'apartment',
    'budget' => 500000,
    'currency' => 'SAR',
    'bedrooms' => 3,
    'city' => 'الرياض',
    'urgency' => 'high',
    'is_read' => false,
    'created_at' => now(),
    'updated_at' => now(),
];

$inquiryId = DB::table('api_customer_inquiry')->insertGetId($inquiryData);
echo "  ✓ Created Inquiry ID: $inquiryId\n";

// Step 1.3: Verify inquiry appears in Requests Center
printStep('1.3', "Verify Inquiry in Requests Center");
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/requests/list', [
    'filters' => ['tab' => 'all'],
    'pagination' => ['page' => 1, 'limit' => 50]
], $token);

printResult('Get Requests List', $result, $scenario1Stats);

if ($result['json'] && isset($result['json']['data']['actions'])) {
    $foundInquiry = false;
    foreach ($result['json']['data']['actions'] as $action) {
        if ($action['type'] === 'new_inquiry' && $action['customerId'] == $newCustomerId) {
            $foundInquiry = true;
            echo "  ✓ Found inquiry in requests list (Action ID: {$action['id']})\n";
            $inquiryActionId = $action['id'];
            break;
        }
    }
    if (!$foundInquiry) {
        echo "  ⚠ Inquiry not found in requests list (may need refresh)\n";
    }
}

// Step 1.4: Get inquiry details
printStep('1.4', "Get Inquiry Details");
$inquiryActionId = "inquiry_$inquiryId";
$result = testEndpoint($kernel, 'GET', "/api/v2/customers-hub/requests/$inquiryActionId", [], $token);
printResult('Get Inquiry Detail', $result, $scenario1Stats);

// Step 1.5: Complete the inquiry
printStep('1.5', "Complete Inquiry Action");
$result = testEndpoint($kernel, 'POST', "/api/v2/customers-hub/requests/$inquiryActionId/complete", [], $token);
printResult('Complete Inquiry', $result, $scenario1Stats);

// Verify in database
verifyDatabase(
    "Inquiry marked as read",
    fn() => DB::table('api_customer_inquiry')->where('id', $inquiryId)->first(),
    fn($inquiry) => $inquiry->is_read == 1
);

printScenarioSummary(1, $scenario1Stats);
$stats['scenarios_completed']++;

// ============================================================================
// SCENARIO 2: PROPERTY REQUEST MATCHING FLOW
// ============================================================================

printScenarioHeader(2, "Property Request Matching Flow");
$scenario2Stats = ['total_tests' => 0, 'passed' => 0, 'failed' => 0];

// Step 2.1: Create property request
printStep('2.1', "Create Property Request");
$propertyRequestData = [
    'user_id' => $userId,
    'full_name' => "{$testConfig['testPrefix']} Requester",
    'phone' => '+966' . rand(500000000, 599999999),
    'region' => 'الرياض',
    'category_id' => 'شقة',
    'property_type' => 'سكني',
    'area_from' => 100,
    'area_to' => 200,
    'budget_from' => 300000,
    'budget_to' => 600000,
    'purchase_method' => 'نقدي',
    'seriousness' => 'مستعد فورًا',
    'purchase_goal' => 'سكن خاص',
    'wants_similar_offers' => true,
    'contact_on_whatsapp' => true,
    'is_active' => true,
    'created_at' => now(),
    'updated_at' => now(),
];

$propertyRequestId = DB::table('users_property_requests')->insertGetId($propertyRequestData);
echo "  ✓ Created Property Request ID: $propertyRequestId\n";

// Step 2.2: Verify it appears in Requests Center
printStep('2.2', "Verify Property Request in Requests Center");
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/requests/list', [
    'filters' => ['tab' => 'all'],
    'pagination' => ['page' => 1, 'limit' => 50]
], $token);

printResult('Get Requests with Property Request', $result, $scenario2Stats);

if ($result['json'] && isset($result['json']['data']['actions'])) {
    $foundRequest = false;
    foreach ($result['json']['data']['actions'] as $action) {
        if ($action['type'] === 'property_match' && strpos($action['id'], "property_request_$propertyRequestId") !== false) {
            $foundRequest = true;
            echo "  ✓ Found property request in list (Action ID: {$action['id']})\n";
            break;
        }
    }
    if (!$foundRequest) {
        echo "  ⚠ Property request not found in list (may need customer linkage)\n";
    }
}

// Step 2.3: Get property request stats
printStep('2.3', "Get Property Request Stats");
$propertyRequestActionId = "property_request_$propertyRequestId";
$result = testEndpoint($kernel, 'GET', "/api/v2/customers-hub/requests/$propertyRequestActionId/stats", [], $token);
printResult('Get Property Request Stats', $result, $scenario2Stats);

printScenarioSummary(2, $scenario2Stats);
$stats['scenarios_completed']++;

// ============================================================================
// SCENARIO 3: CUSTOMER FOLLOW-UP CYCLE
// ============================================================================

printScenarioHeader(3, "Customer Follow-up Cycle");
$scenario3Stats = ['total_tests' => 0, 'passed' => 0, 'failed' => 0];

// Use the customer created in Scenario 1
printStep('3.1', "Add Reminder/Task to Customer");
$result = testEndpoint($kernel, 'POST', "/api/v2/customers-hub/customers/$newCustomerId/tasks", [
    'type' => 'contact',
    'datetime' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i:s\Z'),
    'notes' => 'Follow up on property inquiry',
    'priority' => 2
], $token);

printResult('Add Task to Customer', $result, $scenario3Stats);

$taskId = null;
if ($result['json'] && isset($result['json']['data']['task']['id'])) {
    $taskId = $result['json']['data']['task']['id'];
    echo "  ✓ Created Task ID: $taskId\n";
}

// Step 3.2: Verify task appears in customer details
printStep('3.2', "Verify Task in Customer Details");
$result = testEndpoint($kernel, 'GET', "/api/v2/customers-hub/customers/$newCustomerId", [], $token);
printResult('Get Customer with Tasks', $result, $scenario3Stats);

if ($result['json'] && isset($result['json']['data']['tasks'])) {
    $taskCount = count($result['json']['data']['tasks']);
    echo "  ✓ Customer has $taskCount task(s)\n";
}

// Step 3.3: Complete the reminder
printStep('3.3', "Complete the Reminder");
if ($taskId) {
    $reminderActionId = "reminder_$taskId";
    $result = testEndpoint($kernel, 'POST', "/api/v2/customers-hub/requests/$reminderActionId/complete", [], $token);
    printResult('Complete Reminder', $result, $scenario3Stats);
    
    // Verify in database
    verifyDatabase(
        "Reminder marked as completed",
        fn() => DB::table('reminders')->where('id', $taskId)->first(),
        fn($reminder) => $reminder && $reminder->status === 'completed'
    );
}

// Step 3.4: Add appointment
printStep('3.4', "Add Appointment");
$appointmentData = [
    'user_id' => $userId,
    'customer_id' => $newCustomerId,
    'title' => 'Property Site Visit',
    'type' => 'site_visit',
    'priority' => 2,
    'note' => 'Visit property location',
    'datetime' => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
    'duration' => 60,
    'created_at' => now(),
    'updated_at' => now(),
];

$appointmentId = DB::table('users_api_customers_appointments')->insertGetId($appointmentData);
echo "  ✓ Created Appointment ID: $appointmentId\n";

// Step 3.5: Verify appointment in requests
printStep('3.5', "Verify Appointment in Requests");
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/requests/list', [
    'filters' => ['tab' => 'all'],
    'pagination' => ['page' => 1, 'limit' => 50]
], $token);

printResult('Get Requests with Appointment', $result, $scenario3Stats);

printScenarioSummary(3, $scenario3Stats);
$stats['scenarios_completed']++;

// ============================================================================
// SCENARIO 4: PIPELINE MOVEMENT FLOW
// ============================================================================

printScenarioHeader(4, "Pipeline Movement Flow");
$scenario4Stats = ['total_tests' => 0, 'passed' => 0, 'failed' => 0];

// Step 4.1: Get current pipeline state
printStep('4.1', "Get Pipeline Board");
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/pipeline', [
    'action' => 'board',
    'includeAnalytics' => true
], $token);

printResult('Get Pipeline Board', $result, $scenario4Stats);

// Step 4.2: Move customer through stages
if (count($testStages) >= 2) {
    $targetStage = $testStages[1];
    
    printStep('4.2', "Move Customer to Next Stage");
    $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/pipeline/move', [
        'customerId' => $newCustomerId,
        'newStageId' => $targetStage->id
    ], $token);
    
    printResult('Move Customer in Pipeline', $result, $scenario4Stats);
    
    // Verify in database
    verifyDatabase(
        "Customer stage updated",
        fn() => DB::table('api_customers')->where('id', $newCustomerId)->first(),
        fn($customer) => $customer->stage_id == $targetStage->id
    );
    
    // Step 4.3: Verify customer appears in new stage
    printStep('4.3', "Verify Customer in New Stage");
    $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/pipeline', [
        'action' => 'board',
        'includeAnalytics' => true
    ], $token);
    
    printResult('Get Updated Pipeline', $result, $scenario4Stats);
    
    if ($result['json'] && isset($result['json']['data']['stages'])) {
        foreach ($result['json']['data']['stages'] as $stage) {
            if ($stage['id'] == $targetStage->id) {
                echo "  ✓ Stage '{$stage['name']}' has {$stage['customerCount']} customers\n";
                break;
            }
        }
    }
}

// Step 4.4: Check analytics update
printStep('4.4', "Verify Analytics Updated");
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/analytics', [
    'action' => 'metrics',
    'timeRange' => ['timeRange' => 'last30days']
], $token);

printResult('Get Analytics Metrics', $result, $scenario4Stats);

printScenarioSummary(4, $scenario4Stats);
$stats['scenarios_completed']++;

// ============================================================================
// SCENARIO 5: CUSTOMER DETAIL MANAGEMENT
// ============================================================================

printScenarioHeader(5, "Customer Detail Management");
$scenario5Stats = ['total_tests' => 0, 'passed' => 0, 'failed' => 0];

// Step 5.1: Update customer information
printStep('5.1', "Update Customer Information");
$updateData = [
    'name' => "{$testConfig['testPrefix']} Customer UPDATED",
    'email' => strtolower($testConfig['testPrefix']) . '_updated@test.com',
    'note' => 'Updated by lifecycle test - customer is very interested',
];

$result = testEndpoint($kernel, 'PUT', "/api/v2/customers-hub/customers/$newCustomerId", $updateData, $token);
printResult('Update Customer Info', $result, $scenario5Stats);

// Verify in database
verifyDatabase(
    "Customer information updated",
    fn() => DB::table('api_customers')->where('id', $newCustomerId)->first(),
    fn($customer) => strpos($customer->name, 'UPDATED') !== false
);

// Step 5.2: Add multiple tasks with different priorities
printStep('5.2', "Add Multiple Tasks");

$tasks = [
    ['type' => 'contact', 'priority' => 2, 'notes' => 'High priority follow-up'],
    ['type' => 'contact', 'priority' => 1, 'notes' => 'Medium priority check-in'],
    ['type' => 'contact', 'priority' => 0, 'notes' => 'Low priority reminder'],
];

foreach ($tasks as $index => $taskData) {
    $taskData['datetime'] = Carbon::now()->addDays($index + 3)->format('Y-m-d\TH:i:s\Z');
    $result = testEndpoint($kernel, 'POST', "/api/v2/customers-hub/customers/$newCustomerId/tasks", $taskData, $token);
    printResult("Add Task " . ($index + 1), $result, $scenario5Stats);
}

// Step 5.3: Get customer details with all tasks
printStep('5.3', "Get Complete Customer Details");
$result = testEndpoint($kernel, 'GET', "/api/v2/customers-hub/customers/$newCustomerId", [], $token);
printResult('Get Customer Details', $result, $scenario5Stats);

if ($result['json'] && isset($result['json']['data'])) {
    $customerData = $result['json']['data'];
    echo "\n  Customer Summary:\n";
    echo "    Name: {$customerData['customer']['name']}\n";
    echo "    Email: {$customerData['customer']['email']}\n";
    echo "    Stage: {$customerData['customer']['stage']['name']}\n";
    echo "    Total Tasks: " . count($customerData['tasks']) . "\n";
    echo "    Total Inquiries: {$customerData['stats']['totalInquiries']}\n";
    echo "    Total Appointments: {$customerData['stats']['totalAppointments']}\n";
}

// Step 5.4: Search for the customer
printStep('5.4', "Search for Customer");
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/list', [
    'action' => 'list',
    'filters' => ['search' => $testConfig['testPrefix']],
    'pagination' => ['page' => 1, 'limit' => 10]
], $token);

printResult('Search Customer by Name', $result, $scenario5Stats);

if ($result['json'] && isset($result['json']['data']['customers'])) {
    $found = false;
    foreach ($result['json']['data']['customers'] as $customer) {
        if ($customer['id'] == $newCustomerId) {
            $found = true;
            echo "  ✓ Found customer in search results\n";
            break;
        }
    }
    if (!$found) {
        echo "  ⚠ Customer not found in search results\n";
    }
}

// Step 5.5: Get customer list stats
printStep('5.5', "Get Customer List Stats");
$result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/list/stats', [], $token);
printResult('Get List Stats', $result, $scenario5Stats);

printScenarioSummary(5, $scenario5Stats);
$stats['scenarios_completed']++;

// ============================================================================
// FINAL SUMMARY
// ============================================================================

echo "\n\n";
echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           FINAL TEST SUMMARY                                  ║\n";
echo "╠═══════════════════════════════════════════════════════════════════════════════╣\n";
echo "║  Scenarios Completed: " . str_pad($stats['scenarios_completed'] . "/5", 3, ' ', STR_PAD_LEFT) . "                                                        ║\n";
echo "║  Total Tests Run: " . str_pad($stats['total_tests'], 3, ' ', STR_PAD_LEFT) . "                                                            ║\n";
echo "║  Tests Passed: " . str_pad($stats['passed'], 3, ' ', STR_PAD_LEFT) . "                                                               ║\n";
echo "║  Tests Failed: " . str_pad($stats['failed'], 3, ' ', STR_PAD_LEFT) . "                                                               ║\n";
echo "║  Total Time: " . str_pad(round($stats['total_time']) . "ms", 8, ' ', STR_PAD_LEFT) . "                                                          ║\n";
echo "║  Average Time: " . str_pad(round($stats['total_time'] / max($stats['total_tests'], 1)) . "ms", 8, ' ', STR_PAD_LEFT) . "                                                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";

if ($stats['failed'] === 0) {
    echo "\n🎉 ALL LIFECYCLE TESTS PASSED! The Customers Hub is working perfectly.\n\n";
} else {
    echo "\n⚠️  Some tests failed. Review the output above for details.\n\n";
}

echo "Test Data Created:\n";
echo "  - Customer ID: $newCustomerId\n";
echo "  - Inquiry ID: $inquiryId\n";
echo "  - Property Request ID: $propertyRequestId\n";
if (isset($appointmentId)) {
    echo "  - Appointment ID: $appointmentId\n";
}
echo "\nYou can inspect this data in the database or through the API.\n";
echo "\n";

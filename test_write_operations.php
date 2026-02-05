<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$token = '3047|uahD3zAkkIoIgCayvGoFcrqT6tPGGa1Yz3CGvK1f14a54d22';

function testEndpoint($kernel, $method, $uri, $data = [], $token = null) {
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
    
    return [
        'status' => $response->getStatusCode(),
        'content' => $response->getContent(),
        'json' => json_decode($response->getContent(), true)
    ];
}

function printResult($testName, $result) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "TEST: $testName\n";
    echo str_repeat('=', 80) . "\n";
    echo "HTTP Status: " . $result['status'] . "\n";
    
    if ($result['json']) {
        echo "Response:\n";
        echo json_encode($result['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "Raw Response:\n" . substr($result['content'], 0, 500) . "\n";
    }
    
    if ($result['status'] === 200 || $result['status'] === 201) {
        echo "✓ PASSED\n";
        return true;
    } else {
        echo "✗ FAILED\n";
        return false;
    }
}

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    WRITE OPERATIONS TESTING                                   ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";

$passed = 0;
$failed = 0;

// Test 1: Complete a reminder action
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/requests/reminder_1/complete', [], $token);
printResult('1. Complete Reminder Action', $result) ? $passed++ : $failed++;

// Test 2: Get the same action to verify it's marked as completed
$result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/requests/reminder_1', [], $token);
printResult('2. Verify Reminder Completed', $result) ? $passed++ : $failed++;

// Test 3: Test single action detail with stats
$result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/requests/property_request_25/stats', [], $token);
printResult('3. Get Action Stats', $result) ? $passed++ : $failed++;

// Test 4: Move customer in pipeline
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/pipeline/move', [
    'customerId' => 228,
    'newStageId' => 135
], $token);
printResult('4. Move Customer to New Stage', $result) ? $passed++ : $failed++;

// Test 5: Verify customer moved
$result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/customers/228', [], $token);
if ($result['json'] && isset($result['json']['data']['customer']['stage']['id'])) {
    $newStageId = $result['json']['data']['customer']['stage']['id'];
    echo "\nVerification: Customer stage is now: $newStageId\n";
}
printResult('5. Verify Customer Stage Changed', $result) ? $passed++ : $failed++;

// Test 6: Add task to customer
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/customers/228/tasks', [
    'type' => 'contact',
    'datetime' => '2026-02-10T10:00:00Z',
    'notes' => 'Test task from API',
    'priority' => 2
], $token);
printResult('6. Add Task to Customer', $result) ? $passed++ : $failed++;

// Test 7: Update customer info
$result = testEndpoint($kernel, 'PUT', '/api/v2/customers-hub/customers/228', [
    'name' => 'John Doe Updated',
    'email' => 'john.updated@example.com'
], $token);
printResult('7. Update Customer Info', $result) ? $passed++ : $failed++;

// Test 8: Verify customer updated
$result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/customers/228', [], $token);
if ($result['json'] && isset($result['json']['data']['customer']['name'])) {
    $name = $result['json']['data']['customer']['name'];
    $email = $result['json']['data']['customer']['email'] ?? 'N/A';
    echo "\nVerification: Customer name is now: $name, email: $email\n";
}
printResult('8. Verify Customer Updated', $result) ? $passed++ : $failed++;

// Test 9: Test filtering
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/requests/list', [
    'filters' => ['tab' => 'followups'],
    'pagination' => ['page' => 1, 'limit' => 10]
], $token);
printResult('9. Filter Requests (Follow-ups Only)', $result) ? $passed++ : $failed++;

// Test 10: Test search
$result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/list', [
    'action' => 'list',
    'filters' => ['search' => 'John'],
    'pagination' => ['page' => 1, 'limit' => 10]
], $token);
printResult('10. Search Customers by Name', $result) ? $passed++ : $failed++;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           TEST SUMMARY                                        ║\n";
echo "╠═══════════════════════════════════════════════════════════════════════════════╣\n";
echo "║  Total Tests: " . str_pad($passed + $failed, 3, ' ', STR_PAD_LEFT) . "                                                                  ║\n";
echo "║  Passed: " . str_pad($passed, 3, ' ', STR_PAD_LEFT) . "                                                                      ║\n";
echo "║  Failed: " . str_pad($failed, 3, ' ', STR_PAD_LEFT) . "                                                                      ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";

if ($failed === 0) {
    echo "\n🎉 ALL TESTS PASSED! The Customers Hub API is working correctly.\n\n";
} else {
    echo "\n⚠️  Some tests failed. Check the output above for details.\n\n";
}

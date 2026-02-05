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
    
    // Check for success
    if ($result['status'] === 200 || $result['status'] === 201) {
        echo "✓ PASSED\n";
    } else {
        echo "✗ FAILED\n";
    }
    
    echo "\n";
    
    return $result['status'] === 200 || $result['status'] === 201;
}

$testNum = isset($argv[1]) ? (int)$argv[1] : 0;
$passed = 0;
$failed = 0;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    CUSTOMERS HUB API TESTING                                  ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";

switch ($testNum) {
    case 1:
        $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/requests/list', [
            'action' => 'list',
            'includeStats' => true,
            'filters' => ['tab' => 'all'],
            'pagination' => ['page' => 1, 'limit' => 20]
        ], $token);
        printResult('Get Requests List', $result) ? $passed++ : $failed++;
        break;
        
    case 2:
        $result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/requests/filter-options', [], $token);
        printResult('Get Filter Options', $result) ? $passed++ : $failed++;
        break;
        
    case 3:
        $result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/requests/reminder_1', [], $token);
        printResult('Get Single Action Detail (Reminder)', $result) ? $passed++ : $failed++;
        break;
        
    case 9:
        $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/list', [
            'action' => 'list',
            'includeStats' => true,
            'pagination' => ['page' => 1, 'limit' => 50]
        ], $token);
        printResult('Get Customers List', $result) ? $passed++ : $failed++;
        break;
        
    case 10:
        $result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/list/filter-options', [], $token);
        printResult('Get List Filter Options', $result) ? $passed++ : $failed++;
        break;
        
    case 12:
        $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/pipeline', [
            'action' => 'board',
            'includeAnalytics' => true
        ], $token);
        printResult('Get Pipeline Board', $result) ? $passed++ : $failed++;
        break;
        
    case 15:
        $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/analytics', [
            'action' => 'metrics',
            'timeRange' => ['timeRange' => 'last30days']
        ], $token);
        printResult('Get Analytics Metrics', $result) ? $passed++ : $failed++;
        break;
        
    case 19:
        $result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/customers/228', [], $token);
        printResult('Get Customer Details', $result) ? $passed++ : $failed++;
        break;
        
    case 0:
        // Run all key tests
        echo "Running comprehensive test suite...\n\n";
        
        // Test 1: Requests List
        $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/requests/list', [
            'action' => 'list',
            'includeStats' => true,
            'pagination' => ['page' => 1, 'limit' => 20]
        ], $token);
        printResult('1. Get Requests List', $result) ? $passed++ : $failed++;
        
        // Test 2: Filter Options
        $result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/requests/filter-options', [], $token);
        printResult('2. Get Requests Filter Options', $result) ? $passed++ : $failed++;
        
        // Test 9: Customers List
        $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/list', [
            'action' => 'list',
            'includeStats' => true,
            'pagination' => ['page' => 1, 'limit' => 50]
        ], $token);
        printResult('3. Get Customers List', $result) ? $passed++ : $failed++;
        
        // Test 10: List Filter Options
        $result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/list/filter-options', [], $token);
        printResult('4. Get List Filter Options', $result) ? $passed++ : $failed++;
        
        // Test 12: Pipeline Board
        $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/pipeline', [
            'action' => 'board',
            'includeAnalytics' => true
        ], $token);
        printResult('5. Get Pipeline Board', $result) ? $passed++ : $failed++;
        
        // Test 15: Analytics
        $result = testEndpoint($kernel, 'POST', '/api/v2/customers-hub/analytics', [
            'action' => 'metrics',
            'timeRange' => ['timeRange' => 'last30days']
        ], $token);
        printResult('6. Get Analytics Metrics', $result) ? $passed++ : $failed++;
        
        // Test 19: Customer Detail
        $result = testEndpoint($kernel, 'GET', '/api/v2/customers-hub/customers/228', [], $token);
        printResult('7. Get Customer Details', $result) ? $passed++ : $failed++;
        
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                           TEST SUMMARY                                        ║\n";
        echo "╠═══════════════════════════════════════════════════════════════════════════════╣\n";
        echo "║  Passed: " . str_pad($passed, 3, ' ', STR_PAD_LEFT) . "                                                                      ║\n";
        echo "║  Failed: " . str_pad($failed, 3, ' ', STR_PAD_LEFT) . "                                                                      ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";
        break;
        
    default:
        echo "Usage: php test_endpoints.php [test_number]\n";
        echo "  0  - Run all key tests\n";
        echo "  1  - Get Requests List\n";
        echo "  2  - Get Filter Options\n";
        echo "  3  - Get Single Action Detail\n";
        echo "  9  - Get Customers List\n";
        echo "  10 - Get List Filter Options\n";
        echo "  12 - Get Pipeline Board\n";
        echo "  15 - Get Analytics Metrics\n";
        echo "  19 - Get Customer Details\n";
        break;
}

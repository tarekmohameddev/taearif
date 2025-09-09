<?php

/**
 * Manual Video Size Limit Test Script
 * 
 * This script helps you manually test the video size limit validation
 * by making actual HTTP requests to your API endpoints.
 * 
 * Usage:
 * 1. Update the configuration below
 * 2. Run: php manual_video_test.php
 */

// Configuration
$config = [
    'base_url' => 'http://localhost:8000', // Update this to your app URL
    'api_token' => 'your-api-token-here',  // Update this with a valid API token
    'user_id' => 1,                        // Update this with a valid user ID
];

echo "🎬 Manual Video Size Limit Test\n";
echo "===============================\n\n";

// Test data
$testCases = [
    [
        'name' => 'Small video (30MB) - Should pass',
        'file_size_mb' => 30,
        'expected_status' => 201
    ],
    [
        'name' => 'Large video (75MB) - Should fail',
        'file_size_mb' => 75,
        'expected_status' => 422
    ],
    [
        'name' => 'Exact limit video (50MB) - Should pass',
        'file_size_mb' => 50,
        'expected_status' => 201
    ]
];

function createTestVideoFile($sizeMB) {
    $filename = "test_video_{$sizeMB}mb.mp4";
    $sizeKB = $sizeMB * 1024;
    
    // Create a dummy video file
    $content = str_repeat('0', $sizeKB * 1024); // Create content of specified size
    file_put_contents($filename, $content);
    
    return $filename;
}

function makeApiRequest($url, $data, $token) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "🔧 Configuration:\n";
echo "   Base URL: {$config['base_url']}\n";
echo "   API Token: " . (empty($config['api_token']) ? '❌ Not set' : '✅ Set') . "\n";
echo "   User ID: {$config['user_id']}\n\n";

if (empty($config['api_token']) || $config['api_token'] === 'your-api-token-here') {
    echo "❌ Please update the API token in the configuration section.\n";
    exit(1);
}

echo "🧪 Running test cases...\n\n";

foreach ($testCases as $index => $testCase) {
    echo "Test " . ($index + 1) . ": {$testCase['name']}\n";
    echo str_repeat("-", 50) . "\n";
    
    // Create test video file
    $videoFile = createTestVideoFile($testCase['file_size_mb']);
    echo "📁 Created test file: {$videoFile} ({$testCase['file_size_mb']}MB)\n";
    
    // Prepare request data
    $postData = [
        'title' => 'Test Property ' . ($index + 1),
        'address' => 'Test Address ' . ($index + 1),
        'description' => 'Test Description ' . ($index + 1),
        'featured_image' => 'test-image.jpg',
        'price' => 100000,
        'beds' => 3,
        'bath' => 2,
        'area' => 150,
        'purpose' => 'sale',
        'type' => 'apartment',
        'status' => 1,
        'latitude' => 25.2048,
        'longitude' => 55.2708,
        'city_id' => 1,
        'state_id' => 1,
        'category_id' => 1,
        'video_file' => new CURLFile($videoFile, 'video/mp4', basename($videoFile))
    ];
    
    // Make API request
    $url = $config['base_url'] . '/api/properties';
    $response = makeApiRequest($url, $postData, $config['api_token']);
    
    // Check result
    $statusCode = $response['status_code'];
    $body = $response['body'];
    
    echo "📡 API Response: HTTP {$statusCode}\n";
    
    if ($statusCode === $testCase['expected_status']) {
        echo "✅ Test PASSED - Expected status {$testCase['expected_status']}, got {$statusCode}\n";
    } else {
        echo "❌ Test FAILED - Expected status {$testCase['expected_status']}, got {$statusCode}\n";
    }
    
    if ($statusCode === 422 && isset($body['errors']['video_file'])) {
        echo "📝 Error message: " . $body['errors']['video_file'][0] . "\n";
    } elseif ($statusCode === 201) {
        echo "📝 Success message: " . ($body['message'] ?? 'Property created successfully') . "\n";
    }
    
    // Clean up test file
    unlink($videoFile);
    echo "🗑️  Cleaned up test file\n";
    
    echo "\n";
}

echo "📊 Test Summary:\n";
echo "===============\n";
echo "✅ All tests completed!\n";
echo "\n💡 Next steps:\n";
echo "   1. Check your Laravel logs for any errors\n";
echo "   2. Verify the package video_size_limit is set correctly\n";
echo "   3. Test with different package limits\n";
echo "   4. Run the automated tests: php test_video_validation.php\n";

echo "\n🔗 Useful commands:\n";
echo "   - View logs: tail -f storage/logs/laravel.log\n";
echo "   - Run tests: php artisan test tests/Feature/PropertyVideoSizeLimitTest.php\n";
echo "   - Check routes: php artisan route:list | grep properties\n";

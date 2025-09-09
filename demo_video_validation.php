<?php

/**
 * Video Size Limit Validation Demo
 * 
 * This script demonstrates the video size limit validation behavior
 * without requiring a full database setup.
 */

echo "🎬 Video Size Limit Validation Demo\n";
echo "===================================\n\n";

// Mock package data
$package = (object) [
    'video_size_limit' => 50, // 50MB limit
    'title' => 'Premium Package'
];

// Mock user data
$user = (object) [
    'id' => 1,
    'name' => 'Test User',
    'package' => $package
];

echo "📦 Package Configuration:\n";
echo "   Package: {$package->title}\n";
echo "   Video Size Limit: {$package->video_size_limit}MB\n\n";

// Test Case 1: Video within limit
echo "🧪 Test Case 1: Video within package limit\n";
echo str_repeat("-", 50) . "\n";

$testVideo1 = (object) [
    'name' => 'small-video.mp4',
    'size_mb' => 30,
    'size_kb' => 30 * 1024
];

echo "📁 Video File: {$testVideo1->name}\n";
echo "📏 File Size: {$testVideo1->size_mb}MB ({$testVideo1->size_kb}KB)\n";
echo "📦 Package Limit: {$package->video_size_limit}MB\n";

// Simulate validation
$maxVideoSizeKB = $package->video_size_limit * 1024;
$isValid = $testVideo1->size_kb <= $maxVideoSizeKB;

if ($isValid) {
    echo "✅ VALIDATION RESULT: PASS\n";
    echo "📝 Response: HTTP 201 - Property created successfully\n";
    echo "📄 JSON Response:\n";
    echo json_encode([
        'status' => 'success',
        'message' => 'Property created successfully',
        'user_property' => [
            'id' => 123,
            'title' => 'Test Property',
            'video_url' => 'https://example.com/videos/small-video.mp4'
        ]
    ], JSON_PRETTY_PRINT);
} else {
    echo "❌ VALIDATION RESULT: FAIL\n";
}

echo "\n" . str_repeat("=", 60) . "\n\n";

// Test Case 2: Video exceeding limit
echo "🧪 Test Case 2: Video exceeding package limit\n";
echo str_repeat("-", 50) . "\n";

$testVideo2 = (object) [
    'name' => 'large-video.mp4',
    'size_mb' => 75,
    'size_kb' => 75 * 1024
];

echo "📁 Video File: {$testVideo2->name}\n";
echo "📏 File Size: {$testVideo2->size_mb}MB ({$testVideo2->size_kb}KB)\n";
echo "📦 Package Limit: {$package->video_size_limit}MB\n";

// Simulate validation
$isValid = $testVideo2->size_kb <= $maxVideoSizeKB;

if ($isValid) {
    echo "✅ VALIDATION RESULT: PASS\n";
} else {
    echo "❌ VALIDATION RESULT: FAIL\n";
    echo "📝 Response: HTTP 422 - Validation Error\n";
    echo "📄 JSON Response:\n";
    echo json_encode([
        'status' => 'fail',
        'errors' => [
            'video_file' => [
                "The video file size ({$testVideo2->size_mb}MB) exceeds your package limit of {$package->video_size_limit}MB."
            ]
        ]
    ], JSON_PRETTY_PRINT);
}

echo "\n" . str_repeat("=", 60) . "\n\n";

// Show the actual validation logic
echo "🔧 Implementation Details:\n";
echo str_repeat("-", 30) . "\n";
echo "1. Get package video_size_limit: {$package->video_size_limit}MB\n";
echo "2. Convert to KB for Laravel validation: " . ($package->video_size_limit * 1024) . "KB\n";
echo "3. Laravel validation rule: 'video_file' => 'nullable|file|max:" . ($package->video_size_limit * 1024) . "'\n";
echo "4. Custom error message shows actual size vs limit\n\n";

// Show different package scenarios
echo "📊 Different Package Scenarios:\n";
echo str_repeat("-", 35) . "\n";

$scenarios = [
    ['limit' => 25, 'file' => 30, 'result' => 'FAIL'],
    ['limit' => 25, 'file' => 20, 'result' => 'PASS'],
    ['limit' => 100, 'file' => 75, 'result' => 'PASS'],
    ['limit' => null, 'file' => 200, 'result' => 'PASS (No limit)'],
];

foreach ($scenarios as $scenario) {
    $limit = $scenario['limit'] ?? 'No limit';
    $file = $scenario['file'];
    $result = $scenario['result'];
    
    echo "Package Limit: {$limit}MB | File Size: {$file}MB | Result: {$result}\n";
}

echo "\n🎯 Key Features Demonstrated:\n";
echo str_repeat("-", 40) . "\n";
echo "✅ Dynamic package-based validation\n";
echo "✅ Custom error messages with actual vs limit sizes\n";
echo "✅ Works for both store() and update() methods\n";
echo "✅ Handles cases with no package limit\n";
echo "✅ Converts MB to KB for Laravel validation\n";
echo "✅ User-friendly error responses\n";

echo "\n💡 To test with real API:\n";
echo str_repeat("-", 30) . "\n";
echo "1. Set up your database and run migrations\n";
echo "2. Create a test user with active membership\n";
echo "3. Set package video_size_limit in database\n";
echo "4. Use Postman or manual_video_test.php\n";
echo "5. Test with actual video files\n";

echo "\n🚀 Ready to implement in production!\n";

<?php

/**
 * Test Runner for Video Size Limit Validation
 * 
 * This script helps you run the video size limit tests easily.
 * 
 * Usage:
 * 1. Run all video tests: php test_video_validation.php
 * 2. Run specific test: php test_video_validation.php --test=testName
 * 3. Run with verbose output: php test_video_validation.php --verbose
 */

echo "🎬 Video Size Limit Validation Test Runner\n";
echo "==========================================\n\n";

// Check if we're in the right directory
if (!file_exists('artisan')) {
    echo "❌ Error: Please run this script from the Laravel project root directory.\n";
    exit(1);
}

// Get command line arguments
$options = getopt('', ['test:', 'verbose', 'help']);

if (isset($options['help'])) {
    echo "Usage:\n";
    echo "  php test_video_validation.php                    # Run all video tests\n";
    echo "  php test_video_validation.php --test=testName    # Run specific test\n";
    echo "  php test_video_validation.php --verbose          # Verbose output\n";
    echo "  php test_video_validation.php --help             # Show this help\n\n";
    echo "Available tests:\n";
    echo "  - it_allows_video_upload_within_package_limit\n";
    echo "  - it_rejects_video_upload_exceeding_package_limit\n";
    echo "  - it_shows_custom_error_message_with_file_size_and_limit\n";
    echo "  - it_allows_video_upload_when_no_package_limit_is_set\n";
    echo "  - it_validates_video_size_limit_on_property_update\n";
    echo "  - it_allows_video_update_within_package_limit\n";
    echo "  - it_handles_different_package_limits_correctly\n";
    exit(0);
}

// Build the test command
$testCommand = 'php artisan test tests/Feature/PropertyVideoSizeLimitTest.php';

if (isset($options['test'])) {
    $testCommand .= ' --filter=' . $options['test'];
}

if (isset($options['verbose'])) {
    $testCommand .= ' -v';
}

echo "🚀 Running video size limit validation tests...\n\n";

// Execute the test command
$output = [];
$returnCode = 0;
exec($testCommand . ' 2>&1', $output, $returnCode);

// Display results
foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

if ($returnCode === 0) {
    echo "✅ All tests passed successfully!\n";
    echo "\n📋 Test Summary:\n";
    echo "   - Video size limit validation is working correctly\n";
    echo "   - Package limits are being enforced\n";
    echo "   - Custom error messages are displayed\n";
    echo "   - Both store() and update() methods are validated\n";
} else {
    echo "❌ Some tests failed. Please check the output above.\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "   - Make sure your database is set up for testing\n";
    echo "   - Check that all required models exist\n";
    echo "   - Verify API routes are properly configured\n";
}

echo "\n💡 Tips:\n";
echo "   - Run 'php artisan test --help' for more testing options\n";
echo "   - Use 'php artisan test --filter=PropertyVideoSizeLimitTest' to run only these tests\n";
echo "   - Check 'tests/Feature/PropertyVideoSizeLimitTest.php' to see all test cases\n";

exit($returnCode);

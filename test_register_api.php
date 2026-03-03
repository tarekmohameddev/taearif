<?php
/**
 * Manual test script for POST /api/register
 * Run from project root: php test_register_api.php
 *
 * Uses Laravel's internal request so no external server URL is needed.
 */

// Bootstrap Laravel (same as artisan)
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$timestamp = date('YmdHis');
$payload = [
    'email'       => 'test-register-' . $timestamp . '@example.com',
    'username'    => 'testuser' . $timestamp,
    'password'    => 'password123',
    'phone'       => '5' . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
    'first_name'  => 'Test',
    'last_name'   => 'User',
    'recaptcha_token' => 'TEST_BYPASS_TOKEN',
];

echo "=== Register API Test ===\n";
echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

$json = json_encode($payload);
$request = \Illuminate\Http\Request::create(
    '/api/register',
    'POST',
    [],
    [],
    [],
    ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
    $json
);

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);

$statusCode = $response->getStatusCode();
$body = $response->getContent();

$kernel->terminate($request, $response);

echo "HTTP Status: {$statusCode}\n";
echo "Response:\n";

$decoded = json_decode($body, true);
if ($decoded !== null) {
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo $body;
}

echo "\n\n--- Result ---\n";
if ($statusCode >= 200 && $statusCode < 300) {
    echo "SUCCESS: Registration accepted (HTTP {$statusCode}).\n";
    if (isset($decoded['token'])) {
        echo "Token received: " . substr($decoded['token'], 0, 30) . "...\n";
    }
} else {
    echo "FAILED: HTTP {$statusCode}. Check validation errors above.\n";
}

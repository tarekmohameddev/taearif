<?php
/**
 * Batch fix script for payment controller getUser()->id patterns
 * Phase 3 - Pattern B fixes
 */

$filesToFix = [
    'app/Http/Controllers/User/Payment/StripeController.php' => [29],
    'app/Http/Controllers/User/Payment/MercadopagoController.php' => [34, 95],
    'app/Http/Controllers/User/Payment/RazorpayController.php' => [34, 71],
    'app/Http/Controllers/User/Payment/MollieController.php' => [26, 45],
    'app/Http/Controllers/User/Payment/FlutterWaveController.php' => [26, 78, 86],
    'app/Http/Controllers/User/Payment/PaytmController.php' => [24, 65],
    'app/Http/Controllers/User/Payment/PaypalController.php' => [38],
    'app/Http/Controllers/User/Payment/PaystackController.php' => [33, 65],
    'app/Http/Controllers/User/Payment/OfflineController.php' => [51],
    'app/Http/Controllers/User/Payment/InstamojoController.php' => [22, 35],
    'app/Http/Controllers/User/DonationManagement/Payment/OfflineController.php' => [52],
    'app/Http/Controllers/User/CourseManagement/Payment/OfflineController.php' => [73],
];

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Payment Controllers - getUser() Fix Helper               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "Files to fix: " . count($filesToFix) . "\n";
echo "Total instances: " . array_sum(array_map('count', $filesToFix)) . "\n\n";

foreach ($filesToFix as $file => $lines) {
    echo "📄 " . basename($file) . " - Lines: " . implode(', ', $lines) . "\n";
}

echo "\n";
echo "This script helps identify which files need manual fixing.\n";
echo "Each file should be fixed individually to ensure proper error handling.\n";
echo "\n";
echo "Pattern to use:\n";
echo "  BEFORE: \$userId = getUser()->id;\n";
echo "  AFTER:\n";
echo "    \$user = getUser();\n";
echo "    if (!\$user) {\n";
echo "        abort(404, 'User not found'); // or appropriate error\n";
echo "    }\n";
echo "    \$userId = \$user->id;\n";
echo "\n";


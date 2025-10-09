<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get user ID from command line or prompt
$userId = $argv[1] ?? null;

if (!$userId) {
    echo "Usage: php manual_reseed.php [user_id]\n";
    echo "Or enter your user ID: ";
    $handle = fopen("php://stdin", "r");
    $userId = trim(fgets($handle));
    fclose($handle);
}

if (!$userId || !is_numeric($userId)) {
    echo "Invalid user ID\n";
    exit(1);
}

echo "=== Manual Re-seed for User {$userId} ===\n\n";

$user = \App\Models\User::find($userId);
if (!$user) {
    echo "User not found!\n";
    exit(1);
}

echo "User: {$user->username} ({$user->email})\n\n";

// Check BasicSetting
$bs = \App\Models\User\BasicSetting::where('user_id', $user->id)->first();
if ($bs) {
    echo "BasicSetting found:\n";
    echo "  Logo: {$bs->logo}\n";
    echo "  Company: {$bs->company_name}\n";
    echo "  Expected URL: https://taearif.com/logos/{$bs->logo}\n\n";
} else {
    echo "WARNING: No BasicSetting found!\n\n";
}

echo "Running re-seed...\n";
$seeder = app(\App\Services\TenantWebsiteSeeder::class);
$result = $seeder->reseedWebsite($user);

if ($result) {
    echo "✅ Re-seed completed successfully!\n\n";

    // Verify the update
    $page = \App\Models\TenantPage::where('user_id', $user->id)->first();
    if ($page && $bs) {
        $json = json_encode($page->components);
        $expectedUrl = 'https://taearif.com/logos/' . $bs->logo;

        if (strpos($json, $expectedUrl) !== false) {
            echo "✅ Logo URL is correct in pages!\n";
            echo "   URL: {$expectedUrl}\n";
        } else {
            echo "⚠️  Logo not found in pages\n";
            echo "   Searching for any logo...\n";
            if (preg_match('/logos\/([^"]+)/', $json, $matches)) {
                echo "   Found: https://taearif.com/logos/{$matches[1]}\n";
            }
        }
    }
} else {
    echo "❌ Re-seed failed! Check logs.\n";
}

echo "\nDone!\n";


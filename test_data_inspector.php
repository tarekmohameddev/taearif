<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$token = '3047|uahD3zAkkIoIgCayvGoFcrqT6tPGGa1Yz3CGvK1f14a54d22';

// Extract user_id from token
$tokenParts = explode('|', $token);
$tokenId = (int)$tokenParts[0];

// Get user_id from personal_access_tokens table
$tokenRecord = DB::table('personal_access_tokens')->where('id', $tokenId)->first();
$userId = $tokenRecord ? $tokenRecord->tokenable_id : 1037;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    CUSTOMERS HUB DATA INSPECTOR                               ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "User ID: $userId\n";
echo "Token: $token\n";
echo "\n";

function printSection($title) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "$title\n";
    echo str_repeat('=', 80) . "\n";
}

function printTable($data, $title = null) {
    if ($title) {
        echo "\n$title:\n";
    }
    if (empty($data)) {
        echo "  (No data found)\n";
        return;
    }
    
    foreach ($data as $index => $row) {
        echo "\n  Record #" . ($index + 1) . ":\n";
        foreach ((array)$row as $key => $value) {
            if (is_null($value)) {
                $value = '(null)';
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $value = strlen($value) > 100 ? substr($value, 0, 97) . '...' : $value;
            echo "    " . str_pad($key, 30) . ": $value\n";
        }
    }
}

// 1. API CUSTOMERS
printSection("1. API CUSTOMERS");
$customers = DB::table('api_customers')
    ->where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();
printTable($customers, "Sample Customers (Latest 3)");

$customerCount = DB::table('api_customers')->where('user_id', $userId)->count();
echo "\nTotal Customers: $customerCount\n";

// Get a test-safe customer (one with data but not critical)
$testCustomer = DB::table('api_customers')
    ->where('user_id', $userId)
    ->whereNotNull('stage_id')
    ->orderBy('updated_at', 'desc')
    ->first();
if ($testCustomer) {
    echo "\n✓ Test-Safe Customer ID for modifications: {$testCustomer->id}\n";
    echo "  Name: {$testCustomer->name}\n";
    echo "  Phone: {$testCustomer->phone_number}\n";
}

// 2. CUSTOMER STAGES
printSection("2. CUSTOMER STAGES");
$stages = DB::table('users_api_customers_stages')
    ->where('user_id', $userId)
    ->orderBy('order', 'asc')
    ->get();
printTable($stages, "Available Stages");

// 3. CUSTOMER PRIORITIES
printSection("3. CUSTOMER PRIORITIES");
$priorities = DB::table('users_api_customers_priorities')
    ->where('user_id', $userId)
    ->get();
printTable($priorities, "Available Priorities");

// 4. CUSTOMER TYPES
printSection("4. CUSTOMER TYPES");
$types = DB::table('users_api_customers_types')
    ->where('user_id', $userId)
    ->get();
printTable($types, "Available Types");

// 5. CUSTOMER INQUIRIES
printSection("5. CUSTOMER INQUIRIES");
$inquiries = DB::table('api_customer_inquiry')
    ->where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();
printTable($inquiries, "Sample Inquiries (Latest 3)");

$inquiryCount = DB::table('api_customer_inquiry')->where('user_id', $userId)->count();
echo "\nTotal Inquiries: $inquiryCount\n";

// 6. PROPERTY REQUESTS
printSection("6. PROPERTY REQUESTS");
$propertyRequests = DB::table('users_property_requests')
    ->where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();
printTable($propertyRequests, "Sample Property Requests (Latest 3)");

$propertyRequestCount = DB::table('users_property_requests')->where('user_id', $userId)->count();
echo "\nTotal Property Requests: $propertyRequestCount\n";

// 7. REMINDERS/TASKS
printSection("7. REMINDERS/TASKS");
$reminders = DB::table('reminders')
    ->where('user_id', $userId)
    ->whereNull('deleted_at')
    ->orderBy('datetime', 'desc')
    ->limit(3)
    ->get();
printTable($reminders, "Sample Reminders (Latest 3)");

$reminderCount = DB::table('reminders')
    ->where('user_id', $userId)
    ->whereNull('deleted_at')
    ->count();
echo "\nTotal Active Reminders: $reminderCount\n";

// Get reminder types
$reminderTypes = DB::table('reminder_types')->get();
echo "\nAvailable Reminder Types:\n";
foreach ($reminderTypes as $type) {
    echo "  ID {$type->id}: {$type->name}\n";
}

// 8. APPOINTMENTS
printSection("8. APPOINTMENTS");
$appointments = DB::table('users_api_customers_appointments')
    ->where('user_id', $userId)
    ->orderBy('datetime', 'desc')
    ->limit(3)
    ->get();
printTable($appointments, "Sample Appointments (Latest 3)");

$appointmentCount = DB::table('users_api_customers_appointments')->where('user_id', $userId)->count();
echo "\nTotal Appointments: $appointmentCount\n";

// 9. CITIES AND DISTRICTS
printSection("9. CITIES AND DISTRICTS");
$cities = DB::table('user_cities')
    ->where('user_id', $userId)
    ->limit(5)
    ->get();
printTable($cities, "Sample Cities (First 5)");

$cityCount = DB::table('user_cities')->where('user_id', $userId)->count();
echo "\nTotal Cities: $cityCount\n";

// 10. PROPERTIES (for matching)
printSection("10. USER PROPERTIES");
$properties = DB::table('user_properties')
    ->where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();
printTable($properties, "Sample Properties (Latest 3)");

$propertyCount = DB::table('user_properties')->where('user_id', $userId)->count();
echo "\nTotal Properties: $propertyCount\n";

// 11. RELATIONSHIPS ANALYSIS
printSection("11. DATA RELATIONSHIPS");

// Customers with inquiries
$customersWithInquiries = DB::table('api_customers as c')
    ->join('api_customer_inquiry as i', 'c.id', '=', 'i.customer_id')
    ->where('c.user_id', $userId)
    ->select('c.id', 'c.name', DB::raw('COUNT(i.id) as inquiry_count'))
    ->groupBy('c.id', 'c.name')
    ->orderBy('inquiry_count', 'desc')
    ->limit(5)
    ->get();
echo "\nCustomers with Most Inquiries:\n";
foreach ($customersWithInquiries as $cust) {
    echo "  Customer #{$cust->id} ({$cust->name}): {$cust->inquiry_count} inquiries\n";
}

// Customers with reminders
$customersWithReminders = DB::table('api_customers as c')
    ->join('reminders as r', 'c.id', '=', 'r.customer_id')
    ->where('c.user_id', $userId)
    ->whereNull('r.deleted_at')
    ->select('c.id', 'c.name', DB::raw('COUNT(r.id) as reminder_count'))
    ->groupBy('c.id', 'c.name')
    ->orderBy('reminder_count', 'desc')
    ->limit(5)
    ->get();
echo "\nCustomers with Most Reminders:\n";
foreach ($customersWithReminders as $cust) {
    echo "  Customer #{$cust->id} ({$cust->name}): {$cust->reminder_count} reminders\n";
}

// Stage distribution
$stageDistribution = DB::table('api_customers as c')
    ->leftJoin('users_api_customers_stages as s', 'c.stage_id', '=', 's.id')
    ->where('c.user_id', $userId)
    ->select('s.stage_name', DB::raw('COUNT(c.id) as count'))
    ->groupBy('s.stage_name')
    ->orderBy('count', 'desc')
    ->get();
echo "\nCustomers by Stage:\n";
foreach ($stageDistribution as $stage) {
    $stageName = $stage->stage_name ?? '(No Stage)';
    echo "  {$stageName}: {$stage->count} customers\n";
}

// 12. TEST RECOMMENDATIONS
printSection("12. TEST RECOMMENDATIONS");

echo "\nBased on the data analysis, here are the recommended test IDs:\n\n";

if ($testCustomer) {
    echo "✓ Customer ID for testing: {$testCustomer->id}\n";
    echo "  - Name: {$testCustomer->name}\n";
    echo "  - Current Stage: " . ($testCustomer->stage_id ?? 'None') . "\n";
}

if ($stages->isNotEmpty()) {
    echo "\n✓ Stage IDs for pipeline testing:\n";
    foreach ($stages->take(3) as $stage) {
        echo "  - Stage #{$stage->id}: {$stage->stage_name}\n";
    }
}

if ($priorities->isNotEmpty()) {
    echo "\n✓ Priority IDs for testing:\n";
    foreach ($priorities as $priority) {
        echo "  - Priority #{$priority->id}: {$priority->name}\n";
    }
}

if ($types->isNotEmpty()) {
    echo "\n✓ Type IDs for testing:\n";
    foreach ($types as $type) {
        echo "  - Type #{$type->id}: {$type->name}\n";
    }
}

if ($cities->isNotEmpty()) {
    $city = $cities->first();
    echo "\n✓ City ID for testing: {$city->id} ({$city->name_ar})\n";
}

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           INSPECTION COMPLETE                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "You can now use these IDs in test_real_customer_lifecycle.php\n";
echo "\n";

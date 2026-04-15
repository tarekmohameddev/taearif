<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$upr = DB::table('users_property_requests')->orderByDesc('id')->first();
if (!$upr) {
    echo "no_upr\n";
    exit(0);
}

DB::table('users_property_requests')->where('id', $upr->id)->update(['status_id' => 4]);

$row = DB::table('users_property_requests as upr')
    ->leftJoin('property_request_statuses as prs', 'upr.status_id', '=', 'prs.id')
    ->leftJoin('customers_hub_status_mapping as chsm', 'prs.slug', '=', 'chsm.property_request_status_slug')
    ->where('upr.id', $upr->id)
    ->select(['upr.id', 'upr.status_id', 'prs.slug', 'chsm.customers_hub_status'])
    ->first();

echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";


<?php
namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\ApiInstallation;
use App\Enums\InstallStatus;

class MyFatoorahWebhookController extends Controller
{
    public function handle(Request $req)
    {
        $payload = $req->all()[0] ?? [];
        if (($payload['udf3'] ?? '') !== 'APP') {
            return response()->json(['ignored'=>true]);
        }

        $install = ApiInstallation::where('invoice_id', $payload['PaymentId'] ?? '')
                    ->first();

        if ($install && ($payload['result'] ?? '') === 'CAPTURED') {
            $install->markInstalled($payload['RecurringId'] ?? null);
        }
        return response()->json(['status'=>'ok']);
    }
}

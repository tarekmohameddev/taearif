<?php

namespace App\Http\Controllers\Api\V1\Logs;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Logs\CustomerLog;
use App\Models\Customer;
use App\Http\Controllers\Api\V1\Logs\Concerns\BuildsLogResponses;


class CustomerLogController extends Controller
{
    use BuildsLogResponses;

    public function index(Request $request, int $id)
    {
        $tenantId = $this->resolveTenantId($request);

        $paginator = CustomerLog::where('tenant_id', $tenantId)
            ->where('customer_id', $id)
            ->orderByDesc('id')
            ->paginate(max(1, min(100, (int) $request->integer('per_page', 20))));

        // Get customer information
        $customer = Customer::where('id', $id)
            ->first(['id', 'first_name', 'last_name', 'email', 'username']);

        $customerData = [];
        if ($customer) {
            $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $customerData = [
                'id' => $customer->id,
                'name' => !empty($name) ? $name : null,
                'email' => $customer->email,
                'username' => $customer->username,
            ];
        }

        return $this->respondWithLogs($paginator, $customerData);
    }

}

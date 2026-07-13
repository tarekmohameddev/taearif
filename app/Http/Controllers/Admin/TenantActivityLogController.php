<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantWebsite\SavePagesActivityLogQueryService;
use Illuminate\Http\Request;

class TenantActivityLogController extends Controller
{
    public function __construct(private SavePagesActivityLogQueryService $logs) {}

    public function index(Request $request)
    {
        $term = $request->input('term');

        $tenants = User::where('account_type', 'tenant')
            ->with('generalSettings')
            ->when($term, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('username', 'like', "%$term%")
                      ->orWhere('company_name', 'like', "%$term%")
                      ->orWhere('email', 'like', "%$term%")
                      ->orWhere('phone', 'like', "%$term%")
                      ->orWhereHas('generalSettings', function ($settings) use ($term) {
                          $settings->where('site_name', 'like', "%$term%");
                      });
                });
            })
            ->orderBy('username')
            ->paginate(20)
            ->appends(['term' => $term]);

        return view('admin.tenant_activity_log.index', [
            'tenants' => $tenants,
            'term' => $term,
        ]);
    }

    public function show(Request $request, $tenantId)
    {
        $tenant = User::where('account_type', 'tenant')
            ->with('generalSettings')
            ->findOrFail($tenantId);

        $logs = $this->logs->paginateForTenant($tenant->id, $request);

        return view('admin.tenant_activity_log.show', [
            'tenant' => $tenant,
            'logs' => $logs,
        ]);
    }
}

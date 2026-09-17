<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RevokeWhatsAppQuotaGrantRequest;
use App\Http\Requests\Admin\StoreWhatsAppQuotaGrantRequest;
use App\Models\User;
use App\Models\WhatsappAddon;
use App\Services\Admin\WhatsAppQuotaGrantService;
use App\Services\WhatsApp\WhatsAppQuotaService;
use Illuminate\Http\Request;

class WhatsappQuotaGrantController extends Controller
{
    public function __construct(
        private readonly WhatsAppQuotaGrantService $grantService,
        private readonly WhatsAppQuotaService $quotaService,
    ) {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $tenants = User::query()
            ->whereNull('tenant_id')
            ->where('account_type', 'tenant')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($match) use ($search) {
                    $match->where('id', $search)
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'tenant_page')
            ->withQueryString();

        $tenants->setCollection($tenants->getCollection()->map(function (User $tenant) {
                $tenant->quota_breakdown = $this->quotaService->breakdown($tenant);
                return $tenant;
            }));

        $grants = WhatsappAddon::with(['user:id,username,email', 'audits.admin'])
            ->whereNotNull('user_id')
            ->where('gateway_transaction_id', 'manual_admin_grant')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'grant_page')
            ->withQueryString();

        return view('admin.whatsapp_quota_grants.index', compact('tenants', 'grants', 'search'));
    }

    public function store(StoreWhatsAppQuotaGrantRequest $request)
    {
        $addon = $this->grantService->grant(
            $request->validated(),
            (int) auth('admin')->id(),
            $request->ip()
        );

        return back()->with('success', "WhatsApp quota grant #{$addon->id} was applied.");
    }

    public function revoke(RevokeWhatsAppQuotaGrantRequest $request, WhatsappAddon $grant)
    {
        $grant = $this->grantService->revoke(
            $grant,
            $request->validated('reason'),
            (int) auth('admin')->id(),
            $request->ip()
        );

        $breakdown = $this->quotaService->breakdown($grant->user);
        if ($breakdown['is_over_limit']) {
            return back()->with('warning', "Grant revoked. Usage {$breakdown['usage']} now exceeds quota {$breakdown['quota']}.");
        }

        return back()->with('success', 'WhatsApp quota grant was revoked.');
    }
}

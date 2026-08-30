<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Calling\Models\CallAgentExtension;
use App\Domain\Calling\Models\CallSetting;
use App\Domain\Calling\Models\CallSimLine;
use App\Domain\Calling\Models\CallTrunk;
use App\Domain\Calling\Services\SipProvisioningService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CacheInvalidationHelper;
use Illuminate\Http\Request;
use Throwable;

class CallingTenantController extends Controller
{
    protected SipProvisioningService $provisioning;

    public function __construct(SipProvisioningService $provisioning)
    {
        $this->provisioning = $provisioning;
    }

    public function index(Request $request)
    {
        $query = User::query()
            ->where('account_type', 'tenant')
            ->with('callSetting')
            ->withCount(['callTrunks', 'callSimLines']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('username', 'like', '%' . $search . '%');
            });
        }

        if ($request->input('calling') === 'enabled') {
            $query->whereHas('callSetting', fn ($q) => $q->where('enabled', true));
        } elseif ($request->input('calling') === 'disabled') {
            $query->where(function ($q) {
                $q->whereDoesntHave('callSetting')
                    ->orWhereHas('callSetting', fn ($q2) => $q2->where('enabled', false));
            });
        }

        $tenants = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.calling.tenants.index', compact('tenants'));
    }

    public function show(int $id)
    {
        $tenant = User::where('id', $id)->where('account_type', 'tenant')->firstOrFail();

        $settings = CallSetting::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'enabled'                    => false,
                'record_by_default'          => false,
                'play_recording_announcement'=> false,
                'max_channels'               => 5,
            ]
        );

        $trunks = CallTrunk::where('tenant_id', $tenant->id)
            ->withCount('simLines')
            ->orderByDesc('id')
            ->get();

        $simLines = CallSimLine::where('tenant_id', $tenant->id)
            ->with([
                'trunk:id,name,type',
                'dedicatedAgent:id,first_name,last_name,username,company_name',
            ])
            ->orderByDesc('id')
            ->get();

        $extensions = CallAgentExtension::where('tenant_id', $tenant->id)
            ->with('user:id,first_name,last_name,username,email,company_name')
            ->orderByDesc('id')
            ->get();

        $agents = User::where(function ($q) use ($tenant) {
                $q->where('id', $tenant->id)
                    ->orWhere(function ($q2) use ($tenant) {
                        $q2->where('account_type', 'employee')
                            ->where('tenant_id', $tenant->id);
                    });
            })
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'username', 'company_name', 'account_type']);

        return view('admin.calling.tenants.show', compact(
            'tenant',
            'settings',
            'trunks',
            'simLines',
            'extensions',
            'agents'
        ));
    }

    public function updateSettings(Request $request, int $id)
    {
        $tenant = User::where('id', $id)->where('account_type', 'tenant')->firstOrFail();

        $validated = $request->validate([
            'max_channels' => 'required|integer|min:1|max:50',
        ]);

        $settings = CallSetting::firstOrCreate(['tenant_id' => $tenant->id]);

        $settings->update([
            'enabled'                     => $request->boolean('enabled'),
            'record_by_default'           => $request->boolean('record_by_default'),
            'play_recording_announcement' => $request->boolean('play_recording_announcement'),
            'max_channels'                => (int) $validated['max_channels'],
        ]);

        CacheInvalidationHelper::clearTenantProfileCachesAuto($tenant->id);

        return back()->with('success', __('Calling settings updated.'));
    }

    public function deactivateExtension(int $id, int $extensionId)
    {
        $ext = CallAgentExtension::where('id', $extensionId)
            ->where('tenant_id', $id)
            ->firstOrFail();

        $targetUser = User::findOrFail($ext->user_id);

        try {
            $this->provisioning->deprovisionAgent($targetUser);

            return back()->with('success', __('Extension deactivated.'));
        } catch (Throwable $e) {
            return back()->with('error', __('Failed to deactivate extension: ') . $e->getMessage());
        }
    }
}

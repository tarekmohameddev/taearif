<?php

namespace App\Http\Controllers\Api\Admin\Calling;

use App\Domain\Calling\Models\CallAgentExtension;
use App\Domain\Calling\Models\CallLog;
use App\Domain\Calling\Models\CallSetting;
use App\Domain\Calling\Models\CallTrunk;
use App\Domain\Calling\Services\SipProvisioningService;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Calling\UpdateTenantCallingSettingsRequest;
use App\Http\Resources\Admin\Calling\TenantCallingSettingsResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CallingTenantController extends BaseController
{
    protected SipProvisioningService $provisioning;

    public function __construct(SipProvisioningService $provisioning)
    {
        $this->provisioning = $provisioning;
    }

    /**
     * GET /api/v1/admin/calling/tenants
     *
     * Overview of all tenants that have any calling configuration.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::where('account_type', 'tenant')
            ->whereHas('callSetting')
            ->with('callSetting')
            ->withCount([
                'callTrunks',
                'callSimLines',
            ]);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('first_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('last_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('email', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('username', 'like', '%' . $request->input('search') . '%');
            });
        }

        $tenants = $query->paginate(min((int) $request->input('per_page', 20), 100));

        return $this->successResponse(
            $tenants->through(fn($t) => [
                'id'            => $t->id,
                'name'          => $t->company_name
                    ?: trim(($t->first_name ?? '') . ' ' . ($t->last_name ?? ''))
                    ?: $t->username,
                'email'         => $t->email,
                'username'      => $t->username,
                'calling_enabled' => $t->callSetting?->enabled ?? false,
                'trunks_count'  => $t->call_trunks_count,
                'lines_count'   => $t->call_sim_lines_count,
            ])
        );
    }

    /**
     * GET /api/v1/admin/calling/tenants/{user}/settings
     */
    public function show(int $userId): JsonResponse
    {
        $tenant  = User::where('id', $userId)->where('account_type', 'tenant')->firstOrFail();
        $settings = CallSetting::firstOrCreate(['tenant_id' => $tenant->id], [
            'enabled'             => false,
            'record_by_default'   => false,
            'max_channels'        => 5,
        ]);

        return $this->successResponse(new TenantCallingSettingsResource($settings));
    }

    /**
     * PUT /api/v1/admin/calling/tenants/{user}/settings
     */
    public function update(UpdateTenantCallingSettingsRequest $request, int $userId): JsonResponse
    {
        $tenant   = User::where('id', $userId)->where('account_type', 'tenant')->firstOrFail();
        $settings = CallSetting::firstOrCreate(['tenant_id' => $tenant->id]);

        $settings->update($request->validated());

        return $this->successResponse(new TenantCallingSettingsResource($settings), 'Settings updated.');
    }

    /**
     * GET /api/v1/admin/calling/tenants/{user}/extensions
     */
    public function extensions(int $userId): JsonResponse
    {
        $tenant = User::where('id', $userId)->where('account_type', 'tenant')->firstOrFail();

        $extensions = CallAgentExtension::where('tenant_id', $tenant->id)
            ->with('user:id,first_name,last_name,username,email,company_name')
            ->get();

        return $this->successResponse($extensions->map(fn($e) => [
            'id'           => $e->id,
            'sip_username' => $e->sip_username,
            'extension'    => $e->extension,
            'is_active'    => $e->is_active,
            'user'         => $e->relationLoaded('user') && $e->user ? [
                'id'       => $e->user->id,
                'name'     => trim(($e->user->first_name ?? '') . ' ' . ($e->user->last_name ?? ''))
                    ?: ($e->user->company_name ?? $e->user->username),
                'username' => $e->user->username,
                'email'    => $e->user->email,
            ] : null,
        ]));
    }

    /**
     * DELETE /api/v1/admin/calling/tenants/{user}/extensions/{extension}
     * Force-deactivate an agent extension.
     */
    public function deactivateExtension(int $userId, int $extensionId): JsonResponse
    {
        $ext = CallAgentExtension::where('id', $extensionId)
            ->where('tenant_id', $userId)
            ->firstOrFail();

        $targetUser = User::findOrFail($ext->user_id);

        try {
            $this->provisioning->deprovisionAgent($targetUser);
            return $this->successResponse(null, 'Extension deactivated.');
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to deactivate: ' . $e->getMessage(), 500);
        }
    }
}

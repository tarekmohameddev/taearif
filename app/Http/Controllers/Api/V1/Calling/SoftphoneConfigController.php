<?php

namespace App\Http\Controllers\Api\V1\Calling;

use App\Domain\Calling\Models\CallAgentExtension;
use App\Domain\Calling\Models\CallSetting;
use App\Domain\Calling\Services\SipProvisioningService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\Calling\SoftphoneConfigResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SoftphoneConfigController extends BaseApiController
{
    protected SipProvisioningService $provisioning;

    public function __construct(SipProvisioningService $provisioning)
    {
        $this->provisioning = $provisioning;
    }

    /**
     * GET /api/v1/calling/softphone-config
     *
     * Returns SIP credentials + ICE servers for the authenticated user's softphone.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user     = $request->user();
        $tenantId = $user->tenantOwnerId();

        $settings = CallSetting::where('tenant_id', $tenantId)->first();
        if (!$settings || !$settings->enabled) {
            return $this->errorResponse('Calling is not enabled for your account.', 403);
        }

        $ext = CallAgentExtension::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$ext) {
            return $this->errorResponse('No active SIP extension for your account.', 403);
        }

        $password = $this->provisioning->decryptPassword($ext);

        return $this->successResponse(new SoftphoneConfigResource([
            'extension' => $ext,
            'password'  => $password,
        ]));
    }
}

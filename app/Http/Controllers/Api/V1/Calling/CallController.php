<?php

namespace App\Http\Controllers\Api\V1\Calling;

use App\Domain\Calling\Exceptions\CallingModuleDisabledException;
use App\Domain\Calling\Exceptions\InvalidPhoneNumberException;
use App\Domain\Calling\Exceptions\NoAgentExtensionException;
use App\Domain\Calling\Exceptions\NoAvailableLineException;
use App\Domain\Calling\Models\CallLog;
use App\Domain\Calling\Services\CallOriginationService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Calling\PlaceCallRequest;
use App\Http\Resources\Api\V1\Calling\CallLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CallController extends BaseApiController
{
    protected CallOriginationService $origination;

    public function __construct(CallOriginationService $origination)
    {
        $this->origination = $origination;
    }

    /**
     * POST /api/v1/calling/calls
     */
    public function store(PlaceCallRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $log = $this->origination->placeCall(
                $user,
                $request->input('customer_id'),
                $request->input('to'),
                $request->input('sim_line_id')
            );

            return $this->successResponse(new CallLogResource($log), 'Call initiated.', 201);
        } catch (CallingModuleDisabledException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (InvalidPhoneNumberException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (NoAgentExtensionException | NoAvailableLineException $e) {
            return $this->errorResponse($e->getMessage(), 409);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to place call.', 500);
        }
    }

    /**
     * GET /api/v1/calling/calls/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenantOwnerId();

        $log = CallLog::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with(['customer:id,name,phone_number', 'agent:id,first_name,last_name,username,company_name', 'recording'])
            ->first();

        if (!$log) {
            return $this->errorResponse('Call not found.', 404);
        }

        return $this->successResponse(new CallLogResource($log));
    }

    /**
     * POST /api/v1/calling/calls/{id}/hangup
     */
    public function hangup(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        try {
            $this->origination->hangup($id, $user);
            return $this->successResponse(null, 'Hangup requested.');
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to hangup call.', 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Calling;

use App\Domain\Calling\Models\CallAgentExtension;
use App\Domain\Calling\Models\CallLog;
use App\Domain\Calling\Models\CallSimLine;
use App\Domain\Calling\Services\PhoneNumberService;
use App\Models\ApiCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class InboundRoutingController extends Controller
{
    public function __construct(private readonly PhoneNumberService $phone) {}

    /**
     * POST /api/v1/calling/internal/route-inbound
     *
     * Called by Asterisk dialplan via CURL when an inbound call arrives on a trunk line.
     * Protected by VerifyPbxWebhookSecret middleware.
     *
     * The PBX sends the PJSIP endpoint ID of the SIM line that received the call,
     * plus the caller's raw number. Laravel resolves the tenant, attempts to match
     * the caller to an ApiCustomer, picks a target agent, creates a call_log,
     * and returns dial instructions to the dialplan.
     *
     * Response fields consumed by the Asterisk dialplan:
     *   status         - "ok" | "no_agent" | "error"
     *   call_id        - UUID to set as TAEARIF_CALL_ID
     *   agent_sip_id   - PJSIP endpoint to dial (the agent's WebRTC extension)
     *
     * If status is not "ok", the dialplan should play a busy/not-available message.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'    => ['required', 'string'], // asterisk_endpoint of the sim line that rang
            'caller'      => ['required', 'string'], // raw caller number from Asterisk
        ]);

        $endpointId = $request->input('endpoint');
        $callerRaw  = $request->input('caller');

        // 1. Resolve tenant from the SIM line that received the call
        $line = CallSimLine::where('asterisk_endpoint', $endpointId)
            ->where('is_active', true)
            ->first();

        if (!$line) {
            return response()->json(['status' => 'error', 'reason' => 'unknown_endpoint'], 200);
        }

        $tenantId = $line->tenant_id;

        // 2. Try to match the caller to a customer
        // $callerE164 is set only when normalization succeeds; null otherwise.
        // It is used for from_e164 so the column always holds a valid E.164 value or NULL.
        $customerId = null;
        $callerE164 = null;
        try {
            $callerE164 = $this->phone->toE164($callerRaw);
            $normalized = ltrim($callerE164, '+'); // stored without + by PhoneNormalizer legacy
            $customer   = ApiCustomer::where('user_id', $tenantId)
                ->where(function ($q) use ($callerE164, $normalized) {
                    $q->where('phone_number', $callerE164)
                      ->orWhere('phone_number', $normalized);
                })
                ->first();
            $customerId = $customer?->id;
        } catch (\Throwable) {
            // Caller number is invalid — still route the call, just without a customer link
        }

        // 3. Pick a target agent (line's dedicated agent, else any active agent)
        $agentExt = null;

        if ($line->user_id) {
            $agentExt = CallAgentExtension::where('tenant_id', $tenantId)
                ->where('user_id', $line->user_id)
                ->where('is_active', true)
                ->first();
        }

        if (!$agentExt) {
            $agentExt = CallAgentExtension::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();
        }

        if (!$agentExt) {
            return response()->json(['status' => 'no_agent'], 200);
        }

        // 4. Create inbound call_log
        $callId = (string) Str::uuid();
        CallLog::create([
            'id'          => $callId,
            'tenant_id'   => $tenantId,
            'customer_id' => $customerId,
            'user_id'     => $agentExt->user_id,
            'trunk_id'    => $line->trunk_id,
            'sim_line_id' => $line->id,
            'direction'   => 'inbound',
            'to_e164'     => $line->msisdn,
            'from_e164'   => $callerE164,
            'status'      => 'ringing_agent',
        ]);

        return response()->json([
            'status'       => 'ok',
            'call_id'      => $callId,
            'agent_sip_id' => $agentExt->sip_username,
        ], 200);
    }
}

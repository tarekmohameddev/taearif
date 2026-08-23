<?php

namespace App\Http\Controllers\Api\Admin\Calling;

use App\Domain\Calling\Models\CallTrunk;
use App\Domain\Calling\Services\SipProvisioningService;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Calling\StoreTrunkRequest;
use App\Http\Requests\Admin\Calling\UpdateTrunkRequest;
use App\Http\Resources\Admin\Calling\TrunkResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class CallingTrunkController extends BaseController
{
    protected SipProvisioningService $provisioning;

    public function __construct(SipProvisioningService $provisioning)
    {
        $this->provisioning = $provisioning;
    }

    /**
     * GET /api/v1/admin/calling/trunks
     */
    public function index(Request $request): JsonResponse
    {
        $query = CallTrunk::with(['tenant:id,first_name,last_name,email,username,company_name', 'simLines'])
            ->withCount('simLines');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $trunks = $query->paginate(min((int) $request->input('per_page', 20), 100));

        return $this->successResponse(TrunkResource::collection($trunks)->response()->getData(true));
    }

    /**
     * POST /api/v1/admin/calling/trunks
     */
    public function store(StoreTrunkRequest $request): JsonResponse
    {
        $data = $request->validated();

        $trunk = CallTrunk::create([
            'tenant_id'                => $data['tenant_id'],
            'name'                     => $data['name'],
            'type'                     => $data['type'],
            'ownership'                => $data['ownership'],
            'registration_mode'        => $data['registration_mode'] ?? 'register',
            'asterisk_endpoint_prefix' => 'trunk_' . Str::random(6),
            'status'                   => 'pending',
            'meta'                     => $data['meta'] ?? null,
        ]);

        return $this->successResponse(new TrunkResource($trunk), 'Trunk created.', 201);
    }

    /**
     * GET /api/v1/admin/calling/trunks/{trunk}
     */
    public function show(int $id): JsonResponse
    {
        $trunk = CallTrunk::with(['tenant:id,first_name,last_name,email,username,company_name', 'simLines'])->findOrFail($id);

        return $this->successResponse(new TrunkResource($trunk));
    }

    /**
     * PUT /api/v1/admin/calling/trunks/{trunk}
     */
    public function update(UpdateTrunkRequest $request, int $id): JsonResponse
    {
        $trunk = CallTrunk::findOrFail($id);
        $data  = $request->validated();

        // Never update credentials_encrypted via API — use dedicated provision endpoint
        unset($data['credentials_encrypted']);
        $trunk->update($data);

        return $this->successResponse(new TrunkResource($trunk), 'Trunk updated.');
    }

    /**
     * DELETE /api/v1/admin/calling/trunks/{trunk}
     *
     * Soft-deletes the trunk and removes all its Asterisk endpoints.
     */
    public function destroy(int $id): JsonResponse
    {
        $trunk = CallTrunk::with('simLines')->findOrFail($id);

        try {
            $this->provisioning->deprovisionTrunk($trunk);
            return $this->successResponse(null, 'Trunk removed.');
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to remove trunk: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/admin/calling/trunks/{trunk}/provision-gsm-port
     *
     * Add a Yeastar GSM port (SIM card slot) to an existing trunk.
     */
    public function provisionGsmPort(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'port_index' => 'required|integer|min:1|max:8',
            'msisdn'     => 'required|string|max:20',
            'user_id'    => 'nullable|integer|exists:users,id',
        ]);

        $trunk = CallTrunk::findOrFail($id);

        try {
            $line = $this->provisioning->provisionYeastarPort(
                $trunk,
                (int) $request->input('port_index'),
                $request->input('msisdn'),
                $request->input('user_id')
            );

            return $this->successResponse([
                'sim_line_id'        => $line->id,
                'asterisk_endpoint'  => $line->asterisk_endpoint,
                'pbx_sip_server'     => config('calling.softphone.sip_domain'),
                'pbx_sip_port'       => 5060,
            ], 'GSM port provisioned. Register the device with the credentials above.', 201);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to provision GSM port: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/admin/calling/trunks/{trunk}/provision-stc
     */
    public function provisionStc(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'msisdn'   => 'required|string|max:20',
            'stc_host' => 'required|string|max:255',
        ]);

        $trunk = CallTrunk::findOrFail($id);

        try {
            $line = $this->provisioning->provisionStcTrunk(
                $trunk,
                $request->input('msisdn'),
                $request->input('stc_host')
            );

            return $this->successResponse([
                'sim_line_id'       => $line->id,
                'asterisk_endpoint' => $line->asterisk_endpoint,
            ], 'STC trunk provisioned.', 201);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to provision STC trunk: ' . $e->getMessage(), 500);
        }
    }
}

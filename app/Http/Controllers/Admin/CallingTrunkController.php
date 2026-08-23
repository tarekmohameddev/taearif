<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Calling\Exceptions\InvalidPhoneNumberException;
use App\Domain\Calling\Models\CallTrunk;
use App\Domain\Calling\Services\PhoneNumberService;
use App\Domain\Calling\Services\SipProvisioningService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class CallingTrunkController extends Controller
{
    protected SipProvisioningService $provisioning;
    protected PhoneNumberService $phones;

    public function __construct(SipProvisioningService $provisioning, PhoneNumberService $phones)
    {
        $this->provisioning = $provisioning;
        $this->phones = $phones;
    }

    public function store(Request $request, int $tenantId)
    {
        User::where('id', $tenantId)->where('account_type', 'tenant')->firstOrFail();

        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'type'              => 'required|in:yeastar_gsm,stc_sip',
            'ownership'         => 'required|in:customer_owned,company_hosted',
            'registration_mode' => 'nullable|in:register,ip_auth',
        ]);

        CallTrunk::create([
            'tenant_id'                => $tenantId,
            'name'                     => $validated['name'],
            'type'                     => $validated['type'],
            'ownership'                => $validated['ownership'],
            'registration_mode'        => $validated['registration_mode']
                ?? ($validated['type'] === 'stc_sip' ? 'ip_auth' : 'register'),
            'asterisk_endpoint_prefix' => 'trunk_' . Str::random(6),
            'status'                   => 'pending',
        ]);

        return back()->with('success', __('Trunk created.'));
    }

    public function destroy(int $tenantId, int $trunkId)
    {
        $trunk = CallTrunk::where('id', $trunkId)
            ->where('tenant_id', $tenantId)
            ->with('simLines')
            ->firstOrFail();

        try {
            $this->provisioning->deprovisionTrunk($trunk);

            return back()->with('success', __('Trunk removed.'));
        } catch (Throwable $e) {
            return back()->with('error', __('Failed to remove trunk: ') . $e->getMessage());
        }
    }

    public function provisionGsmPort(Request $request, int $tenantId, int $trunkId)
    {
        $validated = $request->validate([
            'port_index' => 'required|integer|min:1|max:8',
            'msisdn'     => 'required|string|max:20',
            'label'      => 'nullable|string|max:100',
            'user_id'    => 'nullable|integer|exists:users,id',
        ]);

        $trunk = CallTrunk::where('id', $trunkId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if ($trunk->type !== 'yeastar_gsm') {
            return back()->with('error', __('This trunk is not a Yeastar GSM trunk.'));
        }

        try {
            $msisdn = $this->phones->toE164($validated['msisdn']);
            $line = $this->provisioning->provisionYeastarPort(
                $trunk,
                (int) $validated['port_index'],
                $msisdn,
                $validated['user_id'] ?? null
            );

            if (!empty($validated['label'])) {
                $line->update(['label' => $validated['label']]);
            }

            $credentials = [];
            if ($trunk->fresh()->credentials_encrypted) {
                try {
                    $credentials = json_decode(
                        \Illuminate\Support\Facades\Crypt::decryptString($trunk->fresh()->credentials_encrypted),
                        true
                    ) ?? [];
                } catch (Throwable) {
                    $credentials = [];
                }
            }

            $password = $credentials['port_' . $validated['port_index'] . '_password'] ?? null;

            $message = __('GSM port provisioned.') . ' ' . __('Endpoint:') . ' ' . $line->asterisk_endpoint;
            if ($password) {
                $message .= ' — ' . __('SIP password:') . ' ' . $password;
            }

            return back()->with('success', $message);
        } catch (InvalidPhoneNumberException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->withInput()->with('error', __('Failed to provision GSM port: ') . $e->getMessage());
        }
    }

    public function provisionStc(Request $request, int $tenantId, int $trunkId)
    {
        $validated = $request->validate([
            'msisdn'   => 'required|string|max:20',
            'stc_host' => 'required|string|max:255',
            'label'    => 'nullable|string|max:100',
        ]);

        $trunk = CallTrunk::where('id', $trunkId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if ($trunk->type !== 'stc_sip') {
            return back()->with('error', __('This trunk is not an STC SIP trunk.'));
        }

        try {
            $msisdn = $this->phones->toE164($validated['msisdn']);
            $line = $this->provisioning->provisionStcTrunk(
                $trunk,
                $msisdn,
                $validated['stc_host']
            );

            if (!empty($validated['label'])) {
                $line->update(['label' => $validated['label']]);
            }

            return back()->with(
                'success',
                __('STC trunk provisioned.') . ' ' . __('Endpoint:') . ' ' . $line->asterisk_endpoint
            );
        } catch (InvalidPhoneNumberException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->withInput()->with('error', __('Failed to provision STC trunk: ') . $e->getMessage());
        }
    }
}

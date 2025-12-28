<?php

namespace App\Http\Controllers\Api\apps\employee;

use App\Http\Controllers\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Models\EmployeeAddon;
use App\Models\EmployeeAddonPlan;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Payment\ArbController;
use App\Http\Controllers\Payment\MyFatoorahController;

class EmployeeAddonController extends Controller
{
    use ResolvesTenant;

    /**
     * List tenant's employee addons.
     */
    public function index(Request $request)
    {
        $tenantId = $this->tenantId();

        $addons = EmployeeAddon::where('user_id', $tenantId)
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($addon) => [
                'id' => $addon->id,
                'qty' => $addon->qty,
                'amount' => (float) $addon->amount,
                'status' => $addon->status,
                'expire_date' => $addon->expire_date?->toIso8601String(),
                'plan' => $addon->plan ? [
                    'id' => $addon->plan->id,
                    'name' => $addon->plan->name,
                    'duration' => $addon->plan->duration,
                    'duration_unit' => $addon->plan->duration_unit,
                ] : null,
                'created_at' => $addon->created_at?->toIso8601String(),
            ]);

        $owner = $request->user()->tenantOwner();

        return response()->json([
            'success' => true,
            'data' => [
                'addons' => $addons,
                'employee_quota' => $owner->employee_quota,
                'employee_usage' => $owner->employee_usage,
            ],
        ]);
    }

    /**
     * List employee addon plans and tenant's current quota/usage.
     */
    public function plans(Request $request)
    {
        $plans = EmployeeAddonPlan::active()
            ->orderBy('price')
            ->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => (float) $plan->price,
                'duration' => $plan->duration,
                'duration_unit' => $plan->duration_unit,
                'is_active' => $plan->is_active,
            ]);

        $owner = $request->user()->tenantOwner();

        return response()->json([
            'success' => true,
            'data' => [
                'plans' => $plans,
                'employee_quota' => $owner->employee_quota,
                'employee_usage' => $owner->employee_usage,
                'whatsapp_quota' => $owner->whatsapp_quota,
                'whatsapp_usage' => $owner->whatsapp_usage,
            ],
        ]);
    }

    /**
     * Purchase employee addon - creates pending addon and initiates payment.
     */
    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
            'plan_id' => ['required', 'exists:employee_addon_plans,id'],
            'payment_method' => ['required', 'string', 'in:arb,myfatoorah,test'],
        ]);

        $plan = EmployeeAddonPlan::findOrFail($validated['plan_id']);
        if (!$plan->is_active) {
            return response()->json(['success' => false, 'message' => 'الباقة المحددة غير متاحة.'], 422);
        }

        $amount = $plan->price * $validated['qty'];
        $paymentRef = 'EMP_ADDON_' . uniqid() . '_' . rand(1000, 9999);

        $addon = EmployeeAddon::create([
            'user_id' => $tenantId,
            'plan_id' => $plan->id,
            'qty' => $validated['qty'],
            'amount' => $amount,
            'status' => EmployeeAddon::STATUS_PENDING,
            'payment_ref' => $paymentRef,
        ]);

        $paymentResult = $this->initiatePayment($addon, $validated['payment_method'], $request->user());

        if ($paymentResult['success']) {
            return response()->json([
                'status'        => 'success',
                'payment_url'   => $paymentResult['redirect_url'] ?? null,
                'payment_token' => $paymentResult['payment_token'] ?? null,
                'total_amount'  => $amount,
                'package_price' => (float) $plan->price,
                'period'        => (int) $validated['qty'],
                'package_term'  => match ($plan->duration_unit) {
                    'month' => 'monthly',
                    'year'  => 'yearly',
                    default => $plan->duration_unit
                },
            ], 200);
        } else {
            $addon->update(['status' => EmployeeAddon::STATUS_REJECTED]);
            return response()->json([
                'status' => 'error',
                'message' => 'فشل بدء عملية الدفع: ' . ($paymentResult['error'] ?? 'خطأ غير معروف'),
                'payment_url' => null
            ], 422);
        }
    }

    private function initiatePayment(EmployeeAddon $addon, $paymentMethod, $user)
    {
        try {
            $amount = $addon->amount;
            $title = "Employee Addon ({$addon->qty} qty)";

            $successUrl = route('api.employee.addons.payment.success', ['addon_id' => $addon->id, 'gateway' => $paymentMethod]);
            $cancelUrl = route('api.employee.addons.payment.cancel', ['addon_id' => $addon->id, 'gateway' => $paymentMethod]);

            if ($paymentMethod === 'arb') {
                $arb = app(ArbController::class);

                $dummyReq = new Request([
                    'first_name' => $user->first_name ?? $user->name,
                    'last_name' => $user->last_name ?? '',
                    'phone' => $user->phone ?? '',
                    'package_id' => 0,
                ]);

                $result = $arb->paymentProcess(
                    $dummyReq,
                    $amount,
                    $successUrl,
                    $cancelUrl,
                    $title,
                    $user->id,
                    'EMPLOYEE_ADDON',
                    0
                );

                if (isset($result['redirect_url'])) {
                    return [
                        'success' => true,
                        'redirect_url' => $result['redirect_url'],
                        'payment_token' => $result['payment_token'] ?? null
                    ];
                }

                return ['success' => false, 'error' => 'ARB init failed'];

            } elseif ($paymentMethod === 'myfatoorah') {
                return $this->initiateMyFatoorahDirect($addon, $user, $successUrl, $cancelUrl);

            } elseif ($paymentMethod === 'test') {
                return ['success' => true, 'redirect_url' => $successUrl];
            }

            return ['success' => false, 'error' => 'Unsupported method'];

        } catch (\Exception $e) {
            Log::error("Employee Addon Payment Init Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function initiateMyFatoorahDirect($addon, $user, $successUrl, $cancelUrl)
    {
        try {
            $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'myfatoorah')->first();
            if (!$paymentMethod) return ['success' => false, 'error' => 'Gateway not found'];

            $paydata = $paymentMethod->convertAutoData();

            \Config::set('myfatorah.token', $paydata['token']);
            \Config::set('myfatorah.CallBackUrl', $successUrl);
            \Config::set('myfatorah.ErrorUrl', $cancelUrl);

            $myfatoorah = \Basel\MyFatoorah\MyFatoorah::getInstance($paydata['sandbox_status'] == 1);

            $result = $myfatoorah->sendPayment(
                ($user->first_name ?? $user->name),
                $addon->amount,
                [
                    'CustomerMobile' => $paydata['sandbox_status'] == 1 ? '56562123544' : ($user->phone ?? ''),
                    'CustomerReference' => $addon->payment_ref,
                    'UserDefinedField' => $addon->id,
                    "InvoiceItems" => [
                        [
                            "ItemName" => "Employee Addon",
                            "Quantity" => $addon->qty,
                            "UnitPrice" => $addon->amount
                        ]
                    ]
                ]
            );

            if ($result && $result['IsSuccess'] == true) {
                return ['success' => true, 'redirect_url' => $result['Data']['InvoiceURL']];
            }

            return ['success' => false, 'error' => 'MyFatoorah API Error'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Payment success callback - auto-approve addon.
     */
    public function paymentSuccess(Request $request, $addon_id, $gateway)
    {
        try {
            $addon = EmployeeAddon::findOrFail($addon_id);
            if ($addon->status === EmployeeAddon::STATUS_APPROVED) {
                return $this->finalizeRedirect(true, 'Already approved');
            }

            $verified = false;
            $transactionId = null;

            if ($gateway === 'test') {
                if (config('app.env') === 'local') {
                    $verified = true;
                }
            } elseif ($gateway === 'myfatoorah') {
                $paymentId = $request->paymentId;
                if ($paymentId) {
                    try {
                        $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'myfatoorah')->first();
                        if ($paymentMethod) {
                            $paydata = $paymentMethod->convertAutoData();
                            \Config::set('myfatorah.token', $paydata['token']);

                            $myfatoorah = \Basel\MyFatoorah\MyFatoorah::getInstance($paydata['sandbox_status'] == 1);
                            $result = $myfatoorah->getPaymentStatus('paymentId', $paymentId);

                            if ($result && $result['IsSuccess'] == true && $result['Data']['InvoiceStatus'] == "Paid") {
                                $verified = true;
                                $transactionId = $paymentId;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('MyFatoorah Verification Error: ' . $e->getMessage());
                    }
                }
            } elseif ($gateway === 'arb') {
                if ($request->has('trandata')) {
                    $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'arb')->first();
                    if ($paymentMethod) {
                        $paydata = $paymentMethod->convertAutoData();
                        $arb = app(ArbController::class);
                        $decrypted = $arb->decryption($request->trandata, $paydata['resource_key']);

                        if ($decrypted) {
                            $raw = urldecode($decrypted);
                            $dataArr = json_decode($raw, true);

                            if (!empty($dataArr) && is_array($dataArr)) {
                                $paymentData = $dataArr[0];
                                if (isset($paymentData['result']) && $paymentData['result'] === 'CAPTURED') {
                                    $verified = true;
                                    $transactionId = $paymentData['transId'] ?? null;
                                }
                            }
                        }
                    }
                }
            }

            if ($verified) {
                // Calculate expire date from plan
                $expireDate = null;
                if ($addon->plan_id && $addon->plan) {
                    $duration = $addon->plan->duration;
                    $unit = $addon->plan->duration_unit;

                    $expireDate = now();
                    if ($unit === 'day') $expireDate->addDays($duration);
                    elseif ($unit === 'month') $expireDate->addMonths($duration);
                    elseif ($unit === 'year') $expireDate->addYears($duration);
                }

                // Auto-approve: no admin review needed
                $addon->update([
                    'status' => EmployeeAddon::STATUS_APPROVED,
                    'gateway_transaction_id' => $transactionId,
                    'expire_date' => $expireDate,
                ]);

                return $this->finalizeRedirect(true, 'تم الدفع بنجاح');
            }

            return $this->finalizeRedirect(false, 'فشل التحقق من الدفع');

        } catch (\Exception $e) {
            Log::error("Employee Addon Payment Success Error: " . $e->getMessage());
            return $this->finalizeRedirect(false, "خطأ في النظام");
        }
    }

    /**
     * Payment cancel callback.
     */
    public function paymentCancel(Request $request, $addon_id, $gateway)
    {
        $addon = EmployeeAddon::find($addon_id);
        if ($addon && $addon->status === EmployeeAddon::STATUS_PENDING) {
            $addon->update(['status' => EmployeeAddon::STATUS_REJECTED]);
        }
        return $this->finalizeRedirect(false, 'تم إلغاء الدفع');
    }

    private function finalizeRedirect($success, $message)
    {
        if (!$success) {
            return "<h1>{$message}</h1><script>setTimeout(function(){ window.close(); }, 3000);</script>";
        }

        return redirect()->route('success.page')->with('success', $message);
    }
}

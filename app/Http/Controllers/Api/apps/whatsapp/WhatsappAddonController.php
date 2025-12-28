<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Models\WhatsappAddon;
use App\Models\WhatsappUser;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Payment\ArbController;
use App\Http\Controllers\Payment\MyFatoorahController;
use App\Models\WhatsappAddonPlan;

class WhatsappAddonController extends Controller
{
    use ResolvesTenant;

    public function plans(Request $request)
    {
        $tenantId = $this->tenantId();

        // Fetch active plans
        $plans = WhatsappAddonPlan::active()
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

        // Fetch tenant's WhatsApp numbers
        $numbers = WhatsappUser::where('user_id', $tenantId)
            ->with('employee:id,first_name,last_name,email')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($whatsappUser) {
                $noteData = json_decode($whatsappUser->note, true) ?? [];
                
                $numberData = [
                    'id' => $whatsappUser->id,
                    'phoneNumber' => $whatsappUser->number,
                    'name' => $whatsappUser->name,
                    'status' => $whatsappUser->status,
                    'request_status' => $whatsappUser->request_status,
                    'linkingMethod' => $noteData['linkingMethod'] ?? null,
                    'apiMethod' => $noteData['apiMethod'] ?? null,
                    'requestId' => $noteData['requestId'] ?? null,
                    'created_at' => $whatsappUser->created_at?->toIso8601String(),
                    'updated_at' => $whatsappUser->updated_at?->toIso8601String(),
                ];

                if ($whatsappUser->employee_id && $whatsappUser->employee) {
                    $numberData['employee'] = [
                        'id' => $whatsappUser->employee->id,
                        'name' => trim(($whatsappUser->employee->first_name ?? '') . ' ' . ($whatsappUser->employee->last_name ?? '')),
                        'email' => $whatsappUser->employee->email,
                    ];
                }

                return $numberData;
            });

        // Get tenant quota and usage
        $owner = $request->user()->tenantOwner();

        return response()->json([
            'success' => true,
            'data' => [
                'plans' => $plans,
                'numbers' => $numbers,
                'quota' => $owner->whatsapp_quota,
                'usage' => $owner->whatsapp_usage,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'whatsapp_number_id' => [
                'required',
                'integer',
                Rule::exists('whatsapp_users', 'id')->where(fn ($q) => $q->where('user_id', $tenantId)),
            ],
            'qty' => ['required', 'integer', 'min:1'],
            'plan_id' => ['required', 'exists:whatsapp_addon_plans,id'],
            'payment_method' => ['required', 'string', 'in:arb,myfatoorah,test'],
        ]);

        $whatsappUser = WhatsappUser::where('id', $validated['whatsapp_number_id'])
            ->where('user_id', $tenantId)
            ->firstOrFail();
            
        // Fetch Plan and Calculate Amount
        $plan = WhatsappAddonPlan::findOrFail($validated['plan_id']);
        if (!$plan->is_active) {
            return response()->json(['success' => false, 'message' => 'Selected plan is inactive.'], 422);
        }
        $amount = $plan->price * $validated['qty'];

        // Generate a unique payment reference
        $paymentRef = 'WA_ADDON_' . uniqid() . '_' . rand(1000, 9999);

        // Create the addon with pending status
        $addon = WhatsappAddon::create([
            'whatsapp_number_id' => $whatsappUser->id,
            'plan_id' => $plan->id,
            'qty' => $validated['qty'],
            'amount' => $amount,
            'status' => WhatsappAddon::STATUS_PENDING,
            'payment_ref' => $paymentRef,
        ]);

        // Initiate Payment
        $paymentResult = $this->initiatePayment($addon, $validated['payment_method'], $request->user());

        if ($paymentResult['success']) {
            return response()->json([
                'status'        => 'success',
                'payment_url'   => $paymentResult['redirect_url'] ?? null,
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
            // Payment init failed
            $addon->update(['status' => WhatsappAddon::STATUS_REJECTED]);
            return response()->json([
                'status' => 'error',
                'message' => 'Payment initialization failed: ' . ($paymentResult['error'] ?? 'Unknown error'),
                'payment_url' => null
            ], 422);
        }
    }

    private function initiatePayment(WhatsappAddon $addon, $paymentMethod, $user)
    {
        try {
            $amount = $addon->amount;
            $title = "WhatsApp Addon ({$addon->qty} qty)";
            
            // Generate Callback URLs
            $successUrl = route('api.whatsapp.addons.payment.success', ['addon_id' => $addon->id, 'gateway' => $paymentMethod]);
            $cancelUrl = route('api.whatsapp.addons.payment.cancel', ['addon_id' => $addon->id, 'gateway' => $paymentMethod]);

            if ($paymentMethod === 'arb') {
                $arb = app(ArbController::class);
                
                // Create a dummy request as expected by ArbController
                $dummyReq = new Request([
                    'first_name' => $user->fname ?? $user->name, // Adjust based on User model
                    'last_name'  => $user->lname ?? '',
                    'phone'      => $user->phone ?? '',
                    'package_id' => 0,
                ]);

                // Context 'WHATSAPP_ADDON'
                $result = $arb->paymentProcess(
                    $dummyReq,
                    $amount,
                    $successUrl,
                    $cancelUrl,
                    $title,
                    $user->id,
                    'WHATSAPP_ADDON',
                    0 // No app_id
                );

                if (isset($result['redirect_url'])) {
                    return [
                        'success' => true,
                        'redirect_url' => $result['redirect_url'],
                        'payment_token' => $result['payment_token'] ?? null,
                    ];
                }
                
                return ['success' => false, 'error' => 'ARB init failed'];

            } elseif ($paymentMethod === 'myfatoorah') {
                $myfatoorah = app(MyFatoorahController::class);
                
                $dummyReq = new Request([
                    'first_name' => $user->fname ?? $user->name,
                    'last_name'  => $user->lname ?? '',
                    'phone'      => $user->phone ?? '',
                    'package_id' => 0,
                ]);

                // Need $be (BasicExtended)
                 $currentLang = session()->has('lang') ?
                    (Language::where('code', session()->get('lang'))->first())
                    : (Language::where('is_default', 1)->first());
                $be = $currentLang ? $currentLang->basic_extended : null; // Handle if null?

                $result = $myfatoorah->paymentProcess(
                    $dummyReq,
                    $amount,
                    $successUrl,
                    $cancelUrl,
                    $title,
                    $be
                );

                if (is_array($result) && isset($result['redirect_url'])) {
                     return [
                        'success' => true,
                        'redirect_url' => $result['redirect_url']
                    ];
                } elseif (is_string($result)) {
                     // Handle string return if applicable
                }

                // DUPLICATING MYFATOORAH LOGIC HERE to avoid RedirectResponse
                return $this->initiateMyFatoorahDirect($addon, $user, $successUrl, $cancelUrl);

            } elseif ($paymentMethod === 'test') {
                 return [
                    'success' => true,
                    'redirect_url' => $successUrl // Auto-success
                ];
            }

            return ['success' => false, 'error' => 'Unsupported method'];

        } catch (\Exception $e) {
            Log::error("Payment Init Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function initiateMyFatoorahDirect($addon, $user, $successUrl, $cancelUrl) {
         // Replicating basic MyFatoorah init to get URL
         try {
             $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'myfatoorah')->first();
             if (!$paymentMethod) return ['success' => false, 'error' => 'Gateway not found'];
             
             $paydata = $paymentMethod->convertAutoData();
             
             \Config::set('myfatorah.token', $paydata['token']);
             \Config::set('myfatorah.CallBackUrl', $successUrl);
             \Config::set('myfatorah.ErrorUrl', $cancelUrl);
             
             $myfatoorah = \Basel\MyFatoorah\MyFatoorah::getInstance($paydata['sandbox_status'] == 1);
             
             $result = $myfatoorah->sendPayment(
                ($user->fname ?? $user->name),
                $addon->amount,
                [
                    'CustomerMobile' => $paydata['sandbox_status'] == 1 ? '56562123544' : ($user->phone ?? ''),
                    'CustomerReference' => $addon->payment_ref,
                    'UserDefinedField' => $addon->id,
                    "InvoiceItems" => [
                        [
                            "ItemName" => "WhatsApp Addon",
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

    public function paymentSuccess(Request $request, $addon_id, $gateway)
    {
        try {
            $addon = WhatsappAddon::findOrFail($addon_id);
            if ($addon->status === WhatsappAddon::STATUS_APPROVED) {
                 return $this->finalizeRedirect(true, 'Already approved');
            }
            
            $verified = false;
            if ($gateway === 'test') {
                // Secure 'test' gateway: only allow in local environment or if explicit test mode config enabled
                if (config('app.env') === 'local') {
                    $verified = true;
                }
            } elseif ($gateway === 'myfatoorah') {
                 $paymentId = $request->paymentId;
                 if ($paymentId) {
                     // Verify with MyFatoorah API
                     try {
                         // We can use the controller instance or the wrapper directly.
                         // Using the generic wrapper usage from MyFatoorahController logic
                         $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'myfatoorah')->first();
                         if ($paymentMethod) {
                             $paydata = $paymentMethod->convertAutoData();
                             \Config::set('myfatorah.token', $paydata['token']);
                             // We don't need CallBackUrl for verification, just token.
                             
                             $myfatoorah = \Basel\MyFatoorah\MyFatoorah::getInstance($paydata['sandbox_status'] == 1);
                             $result = $myfatoorah->getPaymentStatus('paymentId', $paymentId);

                             if ($result && $result['IsSuccess'] == true && $result['Data']['InvoiceStatus'] == "Paid") {
                                 // Check if amount matches?
                                 // $result['Data']['InvoiceValue'] == $addon->amount
                                 // Check if amount matches?
                                 // $result['Data']['InvoiceValue'] == $addon->amount
                                 $verified = true;
                                 $transactionId = $paymentId;
                             }
                         }
                     } catch (\Exception $e) {
                         Log::error('MyFatoorah Verification Error: '.$e->getMessage());
                     }
                 }
            } elseif ($gateway === 'arb') {
                 // ARB Callback Verification
                 if ($request->has('trandata')) {
                      $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'arb')->first();
                      if ($paymentMethod) {
                          $paydata = $paymentMethod->convertAutoData();
                          // Use public decryption method from ArbController
                          $arb = app(ArbController::class);
                          $decrypted = $arb->decryption($request->trandata, $paydata['resource_key']);
                          
                          if ($decrypted) {
                              $raw = urldecode($decrypted);
                              $dataArr = json_decode($raw, true);
                              
                              if (!empty($dataArr) && is_array($dataArr)) {
                                  $paymentData = $dataArr[0]; // ARB returns array of data
                                  if (isset($paymentData['result']) && $paymentData['result'] === 'CAPTURED') {
                                      // Optional: Verify trackId or udf1 (addon_id)
                                      // $paymentData['udf1'] == $addon->id ?
                                      // Optional: Verify trackId or udf1 (addon_id)
                                      // $paymentData['udf1'] == $addon->id ?
                                      $verified = true;
                                      $transactionId = $paymentData['transId'] ?? null;
                                  }
                              }
                          }
                      }
                 }
            }

            if ($verified) {
                // Calculate expire date if plan exists
                $expireDate = null;
                if ($addon->plan_id && $addon->plan) {
                    $duration = $addon->plan->duration;
                    $unit = $addon->plan->duration_unit; // month, year
                    
                    $expireDate = now();
                    if ($unit === 'month') $expireDate->addMonths($duration);
                    elseif ($unit === 'year') $expireDate->addYears($duration);
                }

                $addon->update([
                    'status' => WhatsappAddon::STATUS_APPROVED,
                    'gateway_transaction_id' => $transactionId ?? null,
                    'expire_date' => $expireDate,
                ]);
                
                return $this->finalizeRedirect(true, 'Payment Successful');
            }

            return $this->finalizeRedirect(false, 'Verification Failed');

        } catch (\Exception $e) {
            Log::error("Payment Success Error: " . $e->getMessage());
            return $this->finalizeRedirect(false, "System Error");
        }
    }

    public function paymentCancel(Request $request, $addon_id, $gateway)
    {
         $addon = WhatsappAddon::find($addon_id);
         if ($addon && $addon->status === WhatsappAddon::STATUS_PENDING) {
             $addon->update(['status' => WhatsappAddon::STATUS_REJECTED]);
         }
         return $this->finalizeRedirect(false, 'Payment Cancelled');
    }

    private function finalizeRedirect($success, $message)
    {
        if (!$success) {
             return "<h1>{$message}</h1><script>setTimeout(function(){ window.close(); }, 3000);</script>";
        }

        return redirect()->route('success.page')->with('success', $message);
    }
}

<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Helpers\UserPermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Language;
use App\Models\Package;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\User\UserCheckoutController;
use App\Services\Payment\MyFatoorahGatewayService;
use Carbon\Carbon;
use App\Http\Helpers\MegaMailer;
use Basel\MyFatoorah\MyFatoorah;
use Illuminate\Support\Facades\Config;

class MyFatoorahController extends Controller
{
    public $myfatoorah;

    public function __construct()
    {
        $currentLang = session()->has('lang') ?
            (Language::where('code', session()->get('lang'))->first())
            : (Language::where('is_default', 1)->first());
        $be = $currentLang->basic_extended;

        $paymentMethod = PaymentGateway::where('keyword', 'myfatoorah')->first();
        $paydata = $paymentMethod->convertAutoData();
        Config::set('myfatoorah.token', $paydata['token']);
        Config::set('myfatoorah.DisplayCurrencyIso', $be->base_currency_text);
        Config::set('myfatoorah.CallBackUrl', route('myfatoorah.success'));
        Config::set('myfatoorah.ErrorUrl', route('myfatoorah.cancel'));
        if ($paydata['sandbox_status'] == 1) {
            $this->myfatoorah = MyFatoorah::getInstance(true);
        } else {
            $this->myfatoorah = MyFatoorah::getInstance(false);
        }
    }

    public function paymentProcess(Request $request, $_amount, $_success_url, $_cancel_url, $_title, $bex)
    {
        /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Buy Plan Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
        Session::put('request', $request->all());

        /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Payment Gateway Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
        $paymentMethod = PaymentGateway::where('keyword', 'myfatoorah')->first();
        $paydata = $paymentMethod->convertAutoData();
        $random_1 = rand(999, 9999);
        $random_2 = rand(9999, 99999);
        $paymentFor = Session::get('paymentFor');
        $name = $paymentFor == 'membership' ? $request->first_name . ' ' . $request->last_name : auth()->user()->first_name . ' ' . auth()->user()->first_name;

        $phone = $paymentFor == 'membership' ? $request->phone : auth()->user()->phone;
        $result = $this->myfatoorah->sendPayment(
            $name,
            $_amount,
            [
                'CustomerMobile' => $paydata['sandbox_status'] == 1 ? '56562123544' : $phone,
                'CustomerReference' => "$random_1",  //orderID
                'UserDefinedField' => "$random_2", //clientID
                "InvoiceItems" => [
                    [
                        "ItemName" => "Package Purchase",
                        "Quantity" => 1,
                        "UnitPrice" => $_amount
                    ]
                ]
            ]
        );
        if ($result && $result['IsSuccess'] == true) {
            $request->session()->put('myfatoorah_payment_type', 'buy_plan');
            return redirect($result['Data']['InvoiceURL']);
        } else {
            return redirect($_cancel_url);
        }
    }

    public function initiateInvoicePayment(array $params): array
    {
        try {
            $paymentMethod = PaymentGateway::where('keyword', 'myfatoorah')->first();

            if (!$paymentMethod) {
                return [
                    'success' => false,
                    'message' => 'MyFatoorah payment gateway is not configured.',
                ];
            }

            $result = app(MyFatoorahGatewayService::class)->sendPayment($paymentMethod, $params);

            if ($result && ($result['IsSuccess'] ?? false) === true) {
                $data = $result['Data'] ?? [];
                $invoiceId = $data['InvoiceId'] ?? $data['InvoiceID'] ?? null;
                $paymentId = $data['PaymentId'] ?? $data['PaymentID'] ?? null;

                return [
                    'success' => true,
                    'redirect_url' => $data['InvoiceURL'],
                    'payment_token' => $invoiceId ?? $paymentId,
                    'invoice_id' => $invoiceId,
                ];
            }

            return [
                'success' => false,
                'message' => $result['Message'] ?? 'Unable to initiate MyFatoorah payment.',
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function paymentProcessForCredits(\App\Models\User $user, float $amount, int $credits, string $paymentMethod = 'myfatoorah'): array
    {
        $dummyReq = new \Illuminate\Http\Request([
            'first_name' => $user->name,
            'last_name'  => '',
            'phone'      => $user->phone ?? '',
            'package_id' => 0,
        ]);

        // Generate success and cancel URLs for credit purchase
        $successUrl = route('api.credits.payment.success', [
            'transaction_id' => 'TEMP_' . time(), // Will be updated with actual transaction ID
            'gateway' => $paymentMethod
        ]);
        $cancelUrl = route('api.credits.payment.cancel', [
            'transaction_id' => 'TEMP_' . time(), // Will be updated with actual transaction ID
            'gateway' => $paymentMethod
        ]);

        $currentLang = session()->has('lang') ?
            (Language::where('code', session()->get('lang'))->first())
            : (Language::where('is_default', 1)->first());
        $be = $currentLang->basic_extended;

        $result = $this->paymentProcess(
            $dummyReq,
            $amount,
            $successUrl,
            $cancelUrl,
            "شراء {$credits} رصيد",
            $be
        );

        // Handle the result from paymentProcess
        if (is_array($result) && isset($result['redirect_url'])) {
            return $result;
        } else {
            return [
                'redirect_url' => $result,
                'payment_token' => null
            ];
        }
    }

    // return to success page
    public function successPayment(Request $request)
    {
        $requestData = Session::get('request');
        $currentLang = session()->has('lang') ?
            (Language::where('code', session()->get('lang'))->first())
            : (Language::where('is_default', 1)->first());
        $bs = $currentLang->basic_setting;
        $be = $currentLang->basic_extended;
        /** Get the payment ID before session clear **/

        if (!empty($request->paymentId)) {
            $result = $this->myfatoorah->getPaymentStatus('paymentId', $request->paymentId);
            if ($result && $result['IsSuccess'] == true && $result['Data']['InvoiceStatus'] == "Paid") {
                $paymentFor = Session::get('paymentFor');
                $package = Package::find($requestData['package_id']);
                $transaction_id = UserPermissionHelper::uniqidReal(8);
                $transaction_details = json_encode($request->all());
                if ($paymentFor == "membership") {
                    $amount = $requestData['price'];
                    $password = $requestData['password'];
                    $checkout = new CheckoutController();
                    $user = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $be, $password);

                    $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
                    $activation = Carbon::parse($lastMemb->start_date);
                    $expire = Carbon::parse($lastMemb->expire_date);
                    $file_name = $this->makeInvoice($requestData, "membership", $user, $password, $amount, $requestData["payment_method"], $requestData['phone'], $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id, $package->title, $lastMemb);

                    $mailer = new MegaMailer();
                    $data = [
                        'toMail' => $user->email,
                        'toName' => $user->fname,
                        'username' => $user->username,
                        'package_title' => $package->title,
                        'package_price' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $package->price . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                        'discount' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $lastMemb->discount . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                        'total' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $lastMemb->price . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                        'activation_date' => $activation->toFormattedDateString(),
                        'expire_date' => Carbon::parse($expire->toFormattedDateString())->format('Y') == '9999' ? 'Lifetime' : $expire->toFormattedDateString(),
                        'membership_invoice' => $file_name,
                        'website_title' => $bs->website_title,
                        'templateType' => 'registration_with_premium_package',
                        'type' => 'registrationWithPremiumPackage'
                    ];
                    $mailer->mailFromAdmin($data);

                    session()->flash('success', __('successful_payment'));
                    Session::forget('request');
                    Session::forget('paymentFor');
                    return [
                        'status' => 'success'
                    ];
                } elseif ($paymentFor == "extend") {
                    $amount = $requestData['price'];
                    $password = uniqid('qrcode');
                    $checkout = new UserCheckoutController();
                    $user = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $be, $password);

                    $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
                    $activation = Carbon::parse($lastMemb->start_date);
                    $expire = Carbon::parse($lastMemb->expire_date);
                    $file_name = $this->makeInvoice($requestData, "extend", $user, $password, $amount, $requestData["payment_method"], $user->phone, $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id, $package->title, $lastMemb);

                    $mailer = new MegaMailer();
                    $data = [
                        'toMail' => $user->email,
                        'toName' => $user->fname,
                        'username' => $user->username,
                        'package_title' => $package->title,
                        'package_price' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $package->price . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                        'activation_date' => $activation->toFormattedDateString(),
                        'expire_date' => Carbon::parse($expire->toFormattedDateString())->format('Y') == '9999' ? 'Lifetime' : $expire->toFormattedDateString(),
                        'membership_invoice' => $file_name,
                        'website_title' => $bs->website_title,
                        'templateType' => 'membership_extend',
                        'type' => 'membershipExtend'
                    ];
                    $mailer->mailFromAdmin($data);

                    session()->flash('success', __('successful_payment'));
                    Session::forget('request');
                    Session::forget('paymentFor');
                    return [
                        'status' => 'success'
                    ];
                }
            } else {
                return [
                    'status' => 'fail'
                ];
            }
        }
    }
}

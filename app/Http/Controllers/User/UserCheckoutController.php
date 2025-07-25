<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Package;
use App\Models\Language;
use App\Models\Membership;
use App\Models\BasicSetting;
use App\Models\OfflineGateway;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Requests\Checkout\ExtendRequest;
use App\Http\Controllers\Payment\PaytmController;
use App\Http\Controllers\Payment\MollieController;
use App\Http\Controllers\Payment\PaypalController;
use App\Http\Controllers\Payment\StripeController;
use App\Http\Controllers\Payment\PaystackController;
use App\Http\Controllers\Payment\RazorpayController;
use App\Http\Controllers\Payment\InstamojoController;
use App\Http\Controllers\Payment\FlutterWaveController;
use App\Http\Controllers\Payment\MercadopagoController;
use App\Http\Controllers\Payment\AuthorizenetController;
use App\Http\Controllers\Payment\PerfectMoneyController;
use App\Http\Controllers\Payment\PhonePeController;
use App\Http\Controllers\Payment\XenditController;
use App\Http\Controllers\Payment\YocoController;
use App\Http\Controllers\Payment\ToyyibpayController;
use App\Http\Controllers\Payment\PaytabsController;
use App\Http\Controllers\Payment\MidtransController;
use App\Http\Controllers\Payment\IyzicoController;
use App\Http\Controllers\Payment\MyFatoorahController;
use App\Http\Controllers\Payment\ArbController;

class UserCheckoutController extends Controller
{
    public function checkout(ExtendRequest $request)
    {
        $offline_payment_gateways = OfflineGateway::all()->pluck('name')->toArray();
        $currentLang = session()->has('lang') ?
            (Language::where('code', session()->get('lang'))->first())
            : (Language::where('is_default', 1)->first());
        $bs = $currentLang->basic_setting;
        $be = $currentLang->basic_extended;
        $request['status'] = "1";
        $request['receipt_name'] = null;
        $request['email'] = auth()->user()->email;
        Session::put('paymentFor', 'extend');
        $title = "You are extending your membership";
        $description = "Congratulation you are going to join our membership.Please make a payment for confirming your membership now!";
        if ($request->price == 0) {
            $request['price'] = 0.00;
            $request['payment_method'] = "-";
            $transaction_details = "Free";
            $password = uniqid('qrcode');
            $package = Package::find($request['package_id']);
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $user = $this->store($request->all(), $transaction_id, $transaction_details, $request['price'], $be, $password);
            $subject = "You made your membership purchase successful";
            $body = "You made a payment. This is a confirmation mail from us. Please see the invoice attachment below";

            $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();

            $file_name = $this->makeInvoice($request->all(), "extend", $user, $password, $request['price'], $request["payment_method"], $user->phone_number, $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id, $package->title, $lastMemb);
            $this->sendMailWithPhpMailer($request->all(), $file_name, $be, $subject, $body, $user->email, $user->first_name . ' ' . $user->last_name);
            Session::forget('request');
            Session::forget('paymentFor');
            return redirect()->route('success.page');
        } elseif ($request->payment_method == "Paypal") {
            $amount = round(($request->price / $be->base_currency_rate), 2);
            $paypal = new PaypalController;
            $cancel_url = route('membership.paypal.cancel');
            $success_url = route('membership.paypal.success');
            return $paypal->paymentProcess($request, $amount, $title, $success_url, $cancel_url);
        } elseif ($request->payment_method == "Stripe") {
            $amount = round(($request->price / $be->base_currency_rate), 2);
            $stripe = new StripeController();
            $cancel_url = route('membership.stripe.cancel');
            return $stripe->paymentProcess($request, $amount, $title, NULL, $cancel_url);
        } elseif ($request->payment_method == "Paytm") {
            if ($be->base_currency_text != "INR") {
                session()->flash('warning', __('only_paytm_INR'));
                return back()->withInput($request->all());
            }
            $amount = $request->price;
            $item_number = uniqid('paytm-') . time();
            $callback_url = route('membership.paytm.status');
            $paytm = new PaytmController();
            return $paytm->paymentProcess($request, $amount, $item_number, $callback_url);
        } elseif ($request->payment_method == "Paystack") {
            if ($be->base_currency_text != "NGN") {
                session()->flash('warning', __('only_paystack_NGN'));
                return back()->withInput($request->all());
            }
            $amount = $request->price * 100;
            $email = $request->email;
            $success_url = route('membership.paystack.success');
            $payStack = new PaystackController();
            return $payStack->paymentProcess($request, $amount, $email, $success_url, $be);
        } elseif ($request->payment_method == "Razorpay") {
            if ($be->base_currency_text != "INR") {
                session()->flash('warning', $be->base_currency_text . " is not allowed for Razorpay");
                return back($request->all());
            }
            $amount = $request->price;
            $item_number = uniqid('razorpay-') . time();
            $cancel_url = route('membership.razorpay.cancel');
            $success_url = route('membership.razorpay.success');
            $razorpay = new RazorpayController();
            return $razorpay->paymentProcess($request, $amount, $item_number, $cancel_url, $success_url, $title, $description, $bs, $be);
        } elseif ($request->payment_method == "Instamojo") {
            if ($be->base_currency_text != "INR") {
                session()->flash('warning', $be->base_currency_text . " is not allowed for Instamojo");
                return back()->withInput($request->all());
            }
            if ($request->price < 9) {
                return redirect()->back()->with('error', 'Minimum 10 INR required for this payment gateway')->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.instamojo.success');
            $cancel_url = route('membership.instamojo.cancel');
            $instaMojo = new InstamojoController();
            return $instaMojo->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Mercado Pago") {
            if ($be->base_currency_text != "BRL") {
                session()->flash('warning', __('only_mercadopago_BRL'));
                return back()->withInput($request->all());
            }
            $amount = $request->price;
            $email = $request->email;
            $success_url = route('membership.mercadopago.success');
            $cancel_url = route('membership.mercadopago.cancel');
            $mercadopagoPayment = new MercadopagoController();
            return $mercadopagoPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $email, $title, $description, $be);
        } elseif ($request->payment_method == "Flutterwave") {
            $available_currency = array(
                'BIF', 'CAD', 'CDF', 'CVE', 'EUR', 'GBP', 'GHS', 'GMD', 'GNF', 'KES', 'LRD', 'MWK', 'NGN', 'RWF', 'SLL', 'STD', 'TZS', 'UGX', 'USD', 'XAF', 'XOF', 'ZMK', 'ZMW', 'ZWD'
            );
            if (!in_array($be->base_currency_text, $available_currency)) {
                session()->flash('warning', $be->base_currency_text . " is not allowed for Flutterwave.");
                return back()->withInput($request->all());
            }
            $amount = $request->price;
            $email = $request->email;
            $item_number = uniqid('flutterwave-') . time();
            $cancel_url = route('membership.flutterwave.cancel');
            $success_url = route('membership.flutterwave.success');
            $flutterWave = new FlutterWaveController();
            return $flutterWave->paymentProcess($request, $amount, $email, $item_number, $success_url, $cancel_url, $be);
        } elseif ($request->payment_method == "Authorize.net") {
            $available_currency = array('USD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'NOK', 'PLN', 'SEK', 'AUD', 'NZD');
            if (!in_array($be->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $cancel_url = route('membership.anet.cancel');
            $anetPayment = new AuthorizenetController();
            return $anetPayment->paymentProcess($request, $amount, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Mollie Payment") {
            $available_currency = array('AED', 'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HRK', 'HUF', 'ILS', 'ISK', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TWD', 'USD', 'ZAR');
            if (!in_array($be->base_currency_text, $available_currency)) {
                session()->flash('warning', $be->base_currency_text . " is not allowed for Mollie");
                return back()->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.mollie.success');
            $cancel_url = route('membership.mollie.cancel');
            $molliePayment = new MollieController();
            return $molliePayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "PhonePe") {
            if ($be->base_currency_text != 'INR') {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.phonepe.success');
            $cancel_url = route('membership.phonepe.cancel');
            $phonepePayment = new PhonePeController();
            return $phonepePayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Perfect Money") {
            if ($be->base_currency_text != 'USD') {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.perfect_money.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $perfectMoneyPayment = new PerfectMoneyController();
            return $perfectMoneyPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Xendit") {
            $allowed_currency = array('IDR', 'PHP', 'USD', 'SGD', 'MYR');
            if (!in_array($be->base_currency_text, $allowed_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.xendit.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $xenditPayment = new XenditController();
            return $xenditPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Yoco") {
            if ($be->base_currency_text != 'ZAR') {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.yoco.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $yocoPayment = new YocoController();
            return $yocoPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Toyyibpay") {
            if ($be->base_currency_text != 'RM') {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.toyyibpay.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $toyyibpay = new ToyyibpayController();
            return $toyyibpay->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Paytabs") {
            $paytabInfo = paytabInfo('admin', null);
            // changing the currency before redirect to Stripe
            if ($be->base_currency_text != $paytabInfo['currency']) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.paytabs.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $paytabs = new PaytabsController();
            return $paytabs->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Midtrans") {
            if ($be->base_currency_text != 'IDR') {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.midtrans.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $midtrans = new MidtransController();
            return $midtrans->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Iyzico") {
            if ($be->base_currency_text != 'TRY') {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.iyzico.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $iyzico = new IyzicoController();
            return $iyzico->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Myfatoorah") {
            // changing the currency before redirect to Stripe
            $allowed_currency = array('KWD', 'SAR', 'BHD', 'AED', 'QAR', 'OMR', 'JOD');
            if (!in_array($be->base_currency_text, $allowed_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = null;
            $cancel_url = route('membership.perfect_money.cancel');
            $myfatoorahPayment = new MyFatoorahController();
            return $myfatoorahPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        }elseif ($request->payment_method == "Arb") {
            // changing the currency before redirect to Stripe

            $amount = $request->price;
            $success_url = route('membership.arb.success');
            $cancel_url = route('membership.arb.cancel');
            $arbPayment = new ArbController();
            return $arbPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif (in_array($request->payment_method, $offline_payment_gateways)) {
            $request['status'] = "0";
            if ($request->hasFile('receipt')) {
                $filename = time() . '.' . $request->file('receipt')->getClientOriginalExtension();
                $directory = public_path("assets/front/img/membership/receipt");
                @mkdir($directory, 0775, true);
                $request->file('receipt')->move($directory, $filename);
                $request['receipt_name'] = $filename;
            }
            $amount = $request->price;
            $transaction_id = \App\Http\Helpers\UserPermissionHelper::uniqidReal(8);
            $transaction_details = "offline";
            $password = uniqid('qrcode');
            $this->store($request, $transaction_id, json_encode($transaction_details), $amount, $be, $password);
            return view('front.offline-success');
        }
    }

    public function store($request, $transaction_id, $transaction_details, $amount, $be, $password)
    {
        $abs = BasicSetting::first();
        Config::set('app.timezone', $abs->timezone);

        $user = User::query()->find($request['user_id']);
        $previousMembership = Membership::query()
            ->select('id', 'package_id', 'is_trial')
            ->where([
                ['user_id', $user->id],
                ['start_date', '<=', Carbon::now()->toDateString()],
                ['expire_date', '>=', Carbon::now()->toDateString()]
            ])
            ->where('status', 1)
            ->orderBy('created_at', 'DESC')
            ->first();
        if (!is_null($previousMembership)) {
            $previousPackage = Package::query()
                ->select('term')
                ->where('id', $previousMembership->package_id)
                ->first();

            if (($previousPackage->term === 'lifetime' || $previousMembership->is_trial == 1) && $transaction_details != '"offline"') {
                $membership = Membership::find($previousMembership->id);
                $membership->expire_date = Carbon::parse($request['start_date']);
                $membership->save();
            }
        }

        if ($user) {
            if (is_array($request)) {
                if (array_key_exists('conversation_id', $request)) {
                    $conversation_id = $request['conversation_id'];
                } else {
                    $conversation_id = null;
                }
            } else {
                $conversation_id = null;
            }
            Membership::create([
                'price' => $request['price'],
                'currency' => $be->base_currency_text,
                'currency_symbol' => $be->base_currency_symbol,
                'payment_method' => $request["payment_method"],
                'transaction_id' => $transaction_id,
                'status' => $request["status"],
                'receipt' => $request["receipt_name"],
                'transaction_details' => $transaction_details,
                'settings' => json_encode($be),
                'package_id' => $request['package_id'],
                'user_id' => $user->id,
                'start_date' => Carbon::parse($request['start_date']),
                'expire_date' => Carbon::parse($request['expire_date']),
                'is_trial' => 0,
                'trial_days' => 0,
                'conversation_id' => $conversation_id
            ]);
        }


        return $user;
    }
}

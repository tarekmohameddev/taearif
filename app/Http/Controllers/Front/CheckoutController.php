<?php

namespace App\Http\Controllers\Front;

use Carbon\Carbon;
use App\Models\User;
use App\Models\User\UserService;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\Language;
use App\Models\User\Menu;
use App\Models\Membership;
use App\Models\BasicSetting;
use Illuminate\Http\Request;
use App\Models\OfflineGateway;
use App\Http\Helpers\MegaMailer;
use App\Models\User\HomeSection;
use App\Models\User\HomePageText;
use Illuminate\Support\Facades\DB;
use App\Models\User\UserPermission;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\User\UserShopSetting;
use Illuminate\Support\Facades\Auth;
use App\Models\User\UserEmailTemplate;
use App\Models\User\UserPaymentGeteway;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Controllers\Payment\YocoController;
use App\Http\Controllers\Payment\PaytmController;
use App\Http\Controllers\Payment\IyzicoController;
use App\Http\Controllers\Payment\MollieController;
use App\Http\Controllers\Payment\PaypalController;
use App\Http\Controllers\Payment\StripeController;
use App\Http\Controllers\Payment\XenditController;
use App\Http\Controllers\Payment\PaytabsController;
use App\Http\Controllers\Payment\PhonePeController;
use App\Http\Controllers\Payment\MidtransController;
use App\Http\Controllers\Payment\PaystackController;
use App\Http\Controllers\Payment\RazorpayController;
use App\Http\Controllers\Payment\InstamojoController;
use App\Http\Controllers\Payment\ToyyibpayController;
use App\Http\Controllers\Payment\MyFatoorahController;
use App\Http\Controllers\Payment\FlutterWaveController;
use App\Http\Controllers\Payment\MercadopagoController;
use App\Http\Controllers\Payment\AuthorizenetController;
use App\Http\Controllers\Payment\PerfectMoneyController;

class CheckoutController extends Controller
{
    public function checkout(CheckoutRequest $request)
    {
        $coupon = Coupon::where('code', Session::get('coupon'))->first();
        if (!empty($coupon)) {
            $coupon_count = $coupon->total_uses;
            if ($coupon->maximum_uses_limit != 999999) {
                if ($coupon_count == $coupon->maximum_uses_limit) {
                    Session::forget('coupon');
                    session()->flash('warning', __('This coupon reached maximum limit'));
                    return redirect()->back();
                }
            }
        }

        $offline_payment_gateways = OfflineGateway::all()->pluck('name')->toArray();
        $currentLang = session()->has('lang') ?
            (Language::where('code', session()->get('lang'))->first())
            : (Language::where('is_default', 1)->first());
        $bs = $currentLang->basic_setting;
        $be = $currentLang->basic_extended;

        $request['status'] = 1;
        $request['mode'] = 'online';
        $request['receipt_name'] = null;
        Session::put('paymentFor', 'membership');
        $title = "You are purchasing a membership";
        $description = "Congratulation you are going to join our membership.Please make a payment for confirming your membership now!";
        if ($request->package_type == "trial") {
            $package = Package::find($request['package_id']);
            $request['price'] = 0.00;
            $request['payment_method'] = "-";
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = "Trial";
            $user = $this->store($request->all(), $transaction_id, $transaction_details, $request->price, $be, $request->password);
            Auth::login($user);
            $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
            $activation = Carbon::parse($lastMemb->start_date);
            $expire = Carbon::parse($lastMemb->expire_date);
            $file_name = $this->makeInvoice($request->all(), "membership", $user, $request->password, $request['price'], "Trial", $request['phone'], $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id, $package->title, $lastMemb);

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
                'templateType' => 'registration_with_trial_package',
                'type' => 'registrationWithTrialPackage'
            ];
            $mailer->mailFromAdmin($data);
            session()->flash('success', __('successful_payment'));
            return redirect()->route('membership.trial.success');
        } elseif ($request->price == 0) {
            $package = Package::find($request['package_id']);
            $request['price'] = 0.00;
            $request['payment_method'] = "-";
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = "Free";
            $user = $this->store($request->all(), $transaction_id, $transaction_details, $request->price, $be, $request->password);
            $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
            $activation = Carbon::parse($lastMemb->start_date);
            $expire = Carbon::parse($lastMemb->expire_date);
            $file_name = $this->makeInvoice($request->all(), "membership", $user, $request->password, $request['price'], "Free", $request['phone'], $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id, $package->title, $lastMemb);

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
                'templateType' => 'registration_with_free_package',
                'type' => 'registrationWithFreePackage'
            ];
            $mailer->mailFromAdmin($data);


            session()->flash('success', __('successful_payment'));
            return redirect()->route('success.page');
        } elseif ($request->payment_method == "Paypal") {

            $amount = round(($request->price / $be->base_currency_rate), 2);

            $paypal = new PaypalController();
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
                return redirect()->back()->with('error', __('only_paytm_INR'))->withInput($request->all());
            }
            $amount = $request->price;
            $item_number = uniqid('paytm-') . time();
            $callback_url = route('membership.paytm.status');
            $paytm = new PaytmController();
            return $paytm->paymentProcess($request, $amount, $item_number, $callback_url);
        } elseif ($request->payment_method == "Paystack") {
            if ($be->base_currency_text != "NGN") {
                return redirect()->back()->with('error', __('only_paystack_NGN'))->withInput($request->all());
            }
            $amount = $request->price * 100;
            $email = $request->email;
            $success_url = route('membership.paystack.success');
            $payStack = new PaystackController();
            return $payStack->paymentProcess($request, $amount, $email, $success_url, $be);
        } elseif ($request->payment_method == "Razorpay") {
            if ($be->base_currency_text != "INR") {
                return redirect()->back()->with('error', __('only_razorpay_INR'))->withInput($request->all());
            }
            $amount = $request->price;
            $item_number = uniqid('razorpay-') . time();
            $cancel_url = route('membership.razorpay.cancel');
            $success_url = route('membership.razorpay.success');
            $razorpay = new RazorpayController();
            return $razorpay->paymentProcess($request, $amount, $item_number, $cancel_url, $success_url, $title, $description, $bs, $be);
        } elseif ($request->payment_method == "Instamojo") {
            if ($be->base_currency_text != "INR") {
                return redirect()->back()->with('error', __('only_instamojo_INR'))->withInput($request->all());
            }
            if ($request->price < 9) {
                session()->flash('warning', 'Minimum 10 INR required for this payment gateway');
                return back()->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.instamojo.success');
            $cancel_url = route('membership.instamojo.cancel');
            $instaMojo = new InstamojoController();
            return $instaMojo->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Mercado Pago") {
            if ($be->base_currency_text != "BRL") {
                return redirect()->back()->with('error', __('only_mercadopago_BRL'))->withInput($request->all());
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
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = round(($request->price / $be->base_currency_rate), 2);
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
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = round(($request->price / $be->base_currency_rate), 2);
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
            $yocoPayment = new ToyyibpayController();
            return $yocoPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Paytabs") {
            $paytabInfo = paytabInfo('admin', null);
            // changing the currency before redirect to Stripe
            if ($be->base_currency_text != $paytabInfo['currency']) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.paytabs.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $paytabPayment = new PaytabsController();
            return $paytabPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Midtrans") {
            if ($be->base_currency_text != 'IDR') {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.midtrans.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $paytabPayment = new MidtransController();
            return $paytabPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Iyzico") {
            if ($be->base_currency_text != 'TRY') {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('membership.iyzico.success');
            $cancel_url = route('membership.perfect_money.cancel');
            $iyzicoPayment = new IyzicoController();
            return $iyzicoPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif ($request->payment_method == "Myfatoorah") {
            $allowed_currency = array('KWD', 'SAR', 'BHD', 'AED', 'QAR', 'OMR', 'JOD');
            if (!in_array($be->base_currency_text, $allowed_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = null;
            $cancel_url = route('membership.perfect_money.cancel');
            $myfatoorahPayment = new MyFatoorahController();
            return $myfatoorahPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $be);
        } elseif (in_array($request->payment_method, $offline_payment_gateways)) {
            $request['mode'] = 'offline';
            $request['status'] = 0;
            $request['receipt_name'] = null;
            if ($request->has('receipt')) {
                $filename = time() . '.' . $request->file('receipt')->getClientOriginalExtension();
                $directory = public_path("assets/front/img/membership/receipt");
                if (!file_exists($directory)) mkdir($directory, 0775, true);
                $request->file('receipt')->move($directory, $filename);
                $request['receipt_name'] = $filename;
            }
            $amount = round(($request->price / $be->base_currency_rate), 2);
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = "offline";
            $password = $request->password;
            $this->store($request, $transaction_id, json_encode($transaction_details), $amount, $be, $password);
            session()->flash('success', __('successful_payment'));
            return redirect()->route('membership.offline.success');
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store($request, $transaction_id, $transaction_details, $amount, $be, $password)
    {
        return DB::transaction(function () use ($request, $transaction_id, $transaction_details, $amount, $be, $password) {


            $deLang = User\Language::firstOrFail();
            $deLang_arabic = User\Language::where('user_id', 0)->firstOrFail();
            $deLanguageNames = json_decode($deLang->keywords, true);
            $deLanguageNames_arabic = json_decode($deLang_arabic->keywords, true);

            $menus = '[
                {"text":"Home","href":"","icon":"empty","target":"_self","title":"","type":"home"},
                {"text":"About","href":"","icon":"empty","target":"_self","title":"","type":"custom","children":[
                    {"text":"Team","href":"","icon":"empty","target":"_self","title":"","type":"team"},
                    {"text":"Career","href":"","icon":"empty","target":"_self","title":"","type":"career"},
                    {"text":"FAQ","href":"","icon":"empty","target":"_self","title":"","type":"faq"}
                ]},
                {"text":"Services","href":"","icon":"empty","target":"_self","title":"","type":"services"},
                {"text":"Blog","href":"","icon":"empty","target":"_self","title":"","type":"blog"},
                {"text":"Contact","href":"","icon":"empty","target":"_self","title":"","type":"contact"}
            ]';

            $menus_ar = '[
                {"text":"Home","href":"","icon":"empty","target":"_self","title":"","type":"home"},
                {"text":"About","href":"","icon":"empty","target":"_self","title":"","type":"custom","children":[
                    {"text":"Team","href":"","icon":"empty","target":"_self","title":"","type":"team"},
                    {"text":"Career","href":"","icon":"empty","target":"_self","title":"","type":"career"},
                    {"text":"FAQ","href":"","icon":"empty","target":"_self","title":"","type":"faq"}
                ]},
                {"text":"Services","href":"","icon":"empty","target":"_self","title":"","type":"services"},
                {"text":"Blog","href":"","icon":"empty","target":"_self","title":"","type":"blog"},
                {"text":"Contact","href":"","icon":"empty","target":"_self","title":"","type":"contact"}
            ]';

            $menus = json_decode($menus, true);
            foreach (array_column($menus, 'text') as $key => $menu) {
                if ($menu == 'Home' && array_key_exists($menu, $deLanguageNames)) {
                    $menus[$key]['text'] = $deLanguageNames[$menu];
                }
                if ($menu == 'About') {
                    $menus[$key]['text'] = array_key_exists('About', $deLanguageNames) ? $deLanguageNames['About'] : 'About';
                    if (isset($menus[$key]['children']) && count($menus[$key]['children']) > 0) {
                        foreach (array_column($menus[$key]['children'], 'text') as $k => $value) {
                            if (in_array($value, ['Team', 'Career', 'FAQ']) && array_key_exists($value, $deLanguageNames)) {
                                $menus[$key]['children'][$k]['text'] = $deLanguageNames[$value];
                            }
                        }
                    }
                }
                if (in_array($menu, ['Services','Blog', 'Contact']) && array_key_exists($menu, $deLanguageNames)) {
                    $menus[$key]['text'] = $deLanguageNames[$menu];
                }
            }

            $menus_arabic = json_decode($menus_ar, true);
            foreach (array_column($menus_arabic, 'text') as $key => $menu) {
                if ($menu == 'Home' && array_key_exists($menu, $deLanguageNames_arabic)) {
                    $menus_arabic[$key]['text'] = $deLanguageNames_arabic[$menu];
                }
                if ($menu == 'About') {
                    $menus_arabic[$key]['text'] = array_key_exists('About', $deLanguageNames_arabic) ? $deLanguageNames_arabic['About'] : 'About';
                    if (isset($menus_arabic[$key]['children']) && count($menus_arabic[$key]['children']) > 0) {
                        foreach (array_column($menus_arabic[$key]['children'], 'text') as $k => $value) {
                            if (in_array($value, ['Team', 'Career', 'FAQ']) && array_key_exists($value, $deLanguageNames_arabic)) {
                                $menus_arabic[$key]['children'][$k]['text'] = $deLanguageNames_arabic[$value];
                            }
                        }
                    }
                }
                if (in_array($menu, ['Services','Blog', 'Contact']) && array_key_exists($menu, $deLanguageNames_arabic)) {
                    $menus_arabic[$key]['text'] = $deLanguageNames_arabic[$menu];
                }
            }
            $menus = json_encode($menus);
            $menus_arabic = json_encode($menus_arabic);


            if (session()->has('lang')) {
                $currentLang = Language::where('code', session()->get('lang'))->first();
            } else {
                $currentLang = Language::where('is_default', 1)->first();
            }


            $bs = $currentLang->basic_setting;
            $token = md5(time() . $request['username'] . $request['email']);
            $verification_link = "<a href='" . url('register/mode/' . $request['mode'] . '/verify/' . $token) . "'>" .
                "<button type=\"button\" class=\"btn btn-primary\">Click Here</button>" .
                "</a>";
            $user = User::where('username', $request['username']);

            if ($user->count() == 0) {
                $user = User::create([
                    'first_name' => $request['first_name'],
                    'last_name' => $request['last_name'],
                    'company_name' => $request['company_name'],
                    'email' => $request['email'],
                    'phone' => $request['phone'],
                    'username' => $request['username'],
                    'password' => bcrypt($password),
                    'status' => $request["status"],
                    'address' => $request["address"] ? $request["address"] : null,
                    'city' => $request["city"] ? $request["city"] : null,
                    'state' => $request["district"] ? $request["district"] : null,
                    'country' => $request["country"] ? $request["country"] : null,
                    'verification_link' => $token,
                ]);

                $deLang = User\Language::firstOrFail();
                $deLang_arabic = User\Language::where('user_id', 0)->firstOrFail();
                $langCount = User\Language::where('user_id', $user->id)->where('is_default', 1)->count();
                if ($langCount == 0) {
                    $lang = new User\Language;
                    $lang->name = $deLang->name;
                    $lang->code = $deLang->code;
                    $lang->is_default = 1;
                    $lang->rtl = $deLang->rtl;
                    $lang->user_id = $user->id;
                    $lang->keywords = $deLang->keywords;
                    $lang->save();

                    // $lang_ar = new User\Language;
                    // $lang_ar->name = $deLang_arabic->name;
                    // $lang_ar->code = $deLang_arabic->code;
                    // $lang_ar->is_default = 1;
                    // $lang_ar->rtl = $deLang_arabic->rtl;
                    // $lang_ar->user_id = $user->id;
                    // $lang_ar->keywords = $deLang_arabic->keywords;
                    // $lang_ar->save();

                    $htext = new HomePageText;
                    $htext->language_id = $lang->id;
                    $htext->user_id = $user->id;
                    $htext->save();

                    $umenu = new Menu();
                    $umenu->language_id = $lang->id;
                    $umenu->user_id = $user->id;
                    $umenu->menus = $menus;
                    $umenu->save();

                    // $umenu = new Menu();
                    // $umenu->language_id = $lang_ar->id;
                    // $umenu->user_id = $user->id;
                    // $umenu->menus = $menus_arabic;
                    // $umenu->save();
                }

                // --- Begin: Basic Settings Record ---
                // Basic Settings Json
                // "favicon": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
                // "email": "F.a.t-550@hotmail.com",
                // "website_title": "شركة ليرا العقارية",
                // "base_color": "0003FF",
                // "secondary_color": "00F5E5",
                // "logo": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",


                $basicSettingsJson = '{

                    "breadcrumb": "https://codecanyon8.kreativdev.com/estaty/assets/img/hero/static/6574372e0ad77.jpg",
                    "preloader": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
                    "theme": "home13",
                    "from_name": null,
                    "is_quote": "1",
                    "qr_image": "6727bead51be1.png",
                    "qr_color": "000000",
                    "qr_size": "248",
                    "qr_style": "square",
                    "qr_eye_style": "square",
                    "qr_margin": "0",
                    "qr_text": null,
                    "qr_text_color": "000000",
                    "qr_text_size": "15",
                    "qr_text_x": "50",
                    "qr_text_y": "50",
                    "qr_inserted_image": null,
                    "qr_inserted_image_size": "20",
                    "qr_inserted_image_x": "50",
                    "qr_inserted_image_y": "50",
                    "qr_type": "default",
                    "qr_url": "https:\/\/taearif.com\/",
                    "whatsapp_status": "0",
                    "whatsapp_number": null,
                    "whatsapp_header_title": null,
                    "whatsapp_popup_status": "0",
                    "whatsapp_popup_message": null,
                    "disqus_status": "0",
                    "disqus_short_name": null,
                    "analytics_status": "0",
                    "measurement_id": null,
                    "pixel_status": "0",
                    "pixel_id": null,
                    "tawkto_status": "0",
                    "tawkto_direct_chat_link": null,
                    "custom_css": null,
                    "base_currency_symbol": "https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg",
                    "base_currency_symbol_position": "left",
                    "base_currency_text": "SAR",
                    "base_currency_rate": null,
                    "base_currency_text_position": null,
                    "is_recaptcha": "0",
                    "google_recaptcha_site_key": null,
                    "google_recaptcha_secret_key": null,
                    "adsense_publisher_id": null,
                    "timezone": "1",
                    "features_section_image": null,
                    "cv": null,
                    "cv_original": null,
                    "email_verification_status": "1",
                    "cookie_alert_status": "0",
                    "cookie_alert_text": null,
                    "cookie_alert_button_text": null,
                    "property_country_status": "1",
                    "property_state_status": "1",
                    "short_description": "شركة ليرا هي شركة عقارية مبتكرة ومتخصصة في تقديم خدمات العقارات بجودة عالية وحلول مهنية. تتميز الشركة بتقديم مجموعة واسعة من العقارات سواء كانت سكنية أو تجارية، وتهدف إلى تلبية احتياجات عملائها من خلال توفير خيارات متنوعة تناسب كافة الأذواق والميزانيات.",
                    "industry_type": "Real Estate Company"
                }';

                $basicSettingsArray = json_decode($basicSettingsJson, true);

                if (isset($basicSettingsArray['id'])) {
                    unset($basicSettingsArray['id']);
                }

                // Override the email with the user's email and user id
                $basicSettingsArray['email'] = $user->email;
                $basicSettingsArray['user_id'] = $user->id;

                User\BasicSetting::create($basicSettingsArray);
                // --- End: Basic Settings Record ---

                // --- Begin: Portfolio Category and Portfolio Records ---
                // Retrieve the default language for the user
                $defaultLanguage = User\Language::where('user_id', $user->id)->where('is_default', 1)->first();
                $secondLanguage = User\Language::where('user_id', $user->id)->where('is_default', 0)->first();

                // Insert portfolio category
                $portfolioCategoryJson = '{
                    "user_id": "",
                    "name": "Consulting",
                    "status": "1",
                    "language_id": "",
                    "serial_number": "1",
                    "created_at": "2021-11-14 17:59:12",
                    "updated_at": "2022-03-12 06:53:01",
                    "featured": "1"
                }';

                $portfolioCategoryArray = json_decode($portfolioCategoryJson, true);
                if (isset($portfolioCategoryArray['id'])) {
                    unset($portfolioCategoryArray['id']);
                }
                $portfolioCategoryArray['user_id'] = $user->id;
                $portfolioCategoryArray['language_id'] = $defaultLanguage->id;
                $portfolioCategory = User\PortfolioCategory::create($portfolioCategoryArray);

                // Insert portfolio
                $portfolioJson = <<<'JSON'
                {
                    "title": "Free Consulting",
                    "slug": "free-consulting-free-consulting",
                    "user_id": "",
                    "image": "1671874201.jpg",
                    "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                    "serial_number": "1",
                    "status": "1",
                    "client_name": "Jorgan Roy",
                    "start_date": "2021-11-19",
                    "submission_date": "2021-02-09",
                    "website_link": "http://example.com/",
                    "featured": "1",
                    "language_id": "",
                    "category_id": "",
                    "meta_keywords": null,
                    "meta_description": null,
                    "created_at": "2021-11-15 00:01:09",
                    "updated_at": "2022-12-24 05:30:01"
                }
                JSON;


                $portfolioArray = json_decode($portfolioJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error: ' . json_last_error_msg());
                }

                // Loop 6 times to insert 6 unique portfolio records.
                for ($i = 1; $i <= 6; $i++) {
                    $portfolioArray = json_decode($portfolioJson, true);
                    if (isset($portfolioArray['id'])) {
                        unset($portfolioArray['id']);
                    }

                    // Set unique title and slug by appending the loop counter.
                    $portfolioArray['title'] = "Free Consulting " . $i;
                    $portfolioArray['slug']  = "free-consulting-" . $i;

                    // Override foreign keys with the actual values.
                    $portfolioArray['user_id'] = $user->id;
                    $portfolioArray['language_id'] = $defaultLanguage->id;
                    $portfolioArray['category_id'] = $portfolioCategory->id;

                    // Ensure that the 'featured' field is set if not already.
                    if (!isset($portfolioArray['featured']) || $portfolioArray['featured'] === '') {
                        $portfolioArray['featured'] = 0;
                    }

                    // Create the portfolio record.
                    User\Portfolio::create($portfolioArray);
                }
                // --- End: Insert Repeated Portfolio Records ---

                // Use nowdoc syntax for valid JSON
                $servicesJson = <<<'JSON'
                [
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "MOBILE APPS",
                        "slug": "mobile-apps",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fab fa-accusoft"
                    },
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "WEB DEVELOPMENT",
                        "slug": "web-development",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fas fa-arrows-alt"
                    },
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "MARKETTING SEO",
                        "slug": "marketting-seo",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fas fa-bell-slash"
                    },
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "GRAPHIC DESIGN",
                        "slug": "graphic-design",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fas fa-address-card"
                    },
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "PLUGIN DEVELOPMENT",
                        "slug": "plugin-development",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fab fa-accusoft"
                    }
                ]
                JSON;

                // Decode JSON and check for errors
                $servicesArray = json_decode($servicesJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for services: ' . json_last_error_msg());
                } else {
                    foreach ($servicesArray as $serviceData) {
                        if (empty($serviceData['id'])) {
                            unset($serviceData['id']);
                        }
                        $serviceData['lang_id'] = $defaultLanguage->id;
                        $serviceData['user_id'] = $user->id;

                        // Insert into the user_services table.
                        \App\Models\User\UserService::create($serviceData);
                    }
                }

                // --- End: Insert Repeated UserService Records ---

                //  insert into user_members table
                $membersJson = <<<'JSON'
                [
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Stuart Clark",
                        "rank": "CEO, Rolan",
                        "image": "77fd8c98cbac033eb9208e5d41671290e9ae65e6.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "1"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Federico Cheisa",
                        "rank": "Manager, Rolan",
                        "image": "ce38744ba92b841ec371066096cfae32ac3fb433.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "1"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Dani Olmo",
                        "rank": "Developer, Rolan",
                        "image": "189ff0cdf780a59aa414f4c5422075b884a5f67b.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "1"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Thiago Silva",
                        "rank": "Designer, Rolan",
                        "image": "bd39661d73f980587b075d225a2ff5a3991c1964.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "1"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Thiago Motta",
                        "rank": "Team Leader, Rolan",
                        "image": "716ece3ac2eefb7a7267c6489d6e99354e8f18c3.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "0"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Chielini",
                        "rank": "Developer, Rolan",
                        "image": "54fab799139d4f815ff7601249f4bb81feb98d29.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "0"
                    }
                ]
                JSON;

                $membersArray = json_decode($membersJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for members: ' . json_last_error_msg());
                } else {
                    foreach ($membersArray as $memberData) {

                        $memberData['language_id'] = $defaultLanguage->id;
                        $memberData['user_id'] = $user->id;

                        \App\Models\User\Member::create($memberData);
                    }
                }

                // --- Insert Blog Categories ---
                $blogCategoriesJson = <<<'JSON'
                [
                    {
                        "name": "Tech",
                        "status": "1",
                        "language_id": "",
                        "user_id": "",
                        "serial_number": "1",
                        "created_at": "2021-11-14 19:55:43",
                        "updated_at": "2021-11-14 19:55:43"
                    },
                    {
                        "name": "Entertainment",
                        "status": "1",
                        "language_id": "",
                        "user_id": "",
                        "serial_number": "2",
                        "created_at": "2021-11-14 19:55:57",
                        "updated_at": "2021-11-14 19:55:57"
                    },
                    {
                        "name": "Corporate",
                        "status": "1",
                        "language_id": "",
                        "user_id": "",
                        "serial_number": "3",
                        "created_at": "2021-11-14 19:56:17",
                        "updated_at": "2021-11-14 19:56:17"
                    },
                    {
                        "name": "تقنية",
                        "status": "1",
                        "language_id": "",
                        "user_id": "",
                        "serial_number": "1",
                        "created_at": "2021-11-14 19:55:43",
                        "updated_at": "2021-11-14 19:55:43"
                    },
                    {
                        "name": "تسلية",
                        "status": "1",
                        "language_id": "",
                        "user_id": "",
                        "serial_number": "2",
                        "created_at": "2021-11-14 19:55:57",
                        "updated_at": "2021-11-14 19:55:57"
                    },
                    {
                        "name": "شركة كبرى",
                        "status": "1",
                        "language_id": "",
                        "user_id": "",
                        "serial_number": "3",
                        "created_at": "2021-11-14 19:56:17",
                        "updated_at": "2021-11-14 19:56:17"
                    }
                ]
                JSON;

                $blogCategoriesArray = json_decode($blogCategoriesJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for blog categories: ' . json_last_error_msg());
                } else {
                    $blogCategoryModels = [];
                    foreach ($blogCategoriesArray as $categoryData) {
                        if (empty($categoryData['id'])) {
                            unset($categoryData['id']);
                        }
                        $categoryData['language_id'] = $defaultLanguage->id;
                        $categoryData['user_id']       = $user->id;
                        $blogCategory = \App\Models\User\BlogCategory::create($categoryData);
                        $blogCategoryModels[] = $blogCategory;
                    }
                }

                // --- Insert Blogs and Relate Them to a Category ---
                $blogsJson = <<<'JSON'
                [
                    {
                        "language_id": "",
                        "bcategory_id": "",
                        "title": "وقد نجا خمسة قرون فحسب",
                        "slug": "وقد-نجا-خمسة-قرون-فحسب",
                        "main_image": "1637216494.png",
                        "content": "But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it but who has any right to find fault with a man who choosesNo one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure"
                    },
                    {
                        "language_id": "",
                        "bcategory_id": "",
                        "title": "من ناحية أخرى ، نشجب بسخط مستقيم",
                        "slug": "من-ناحية-أخرى-،-نشجب-بسخط-مستقيم",
                        "main_image": "1637216524.png",
                        "content": "But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it but who has any right to find fault with a man who choosesNo one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure"
                    },
                    {
                        "language_id": "",
                        "bcategory_id": "",
                        "title": "إنه يرغب في الحصول على الاحتياطي الفيدرالي ، ولكن أيضًا لأنه لا يمكن إلحاقه أبدًا",
                        "slug": "إنه-يرغب-في-الحصول-على-الاحتياطي-الفيدرالي-،-ولكن-أيضًا-لأنه-لا-يمكن-إلحاقه-أبدًا",
                        "main_image": "1637216530.png",
                        "content": "But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it but who has any right to find fault with a man who choosesNo one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure"
                    }
                ]
                JSON;

                $blogsArray = json_decode($blogsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for blogs: ' . json_last_error_msg());
                } else {
                    $arabicBlogCategories = array_filter($blogCategoryModels, function ($category) {
                        return (bool) preg_match('/\p{Arabic}/u', $category->name);
                    });

                    $arabicCategoriesBySerial = [];
                    foreach ($arabicBlogCategories as $cat) {
                        $arabicCategoriesBySerial[(int)$cat->serial_number] = $cat;
                    }

                    foreach ($blogsArray as $blogData) {
                        if (empty($blogData['id'])) {
                            unset($blogData['id']);
                        }
                        $blogData['language_id'] = $defaultLanguage->id;
                        $blogData['user_id']       = $user->id;

                        if (isset($blogData['main_image'])) {
                            $blogData['image'] = $blogData['main_image'];
                            unset($blogData['main_image']);
                        }

                        $blogSerial = isset($blogData['serial_number']) ? (int)$blogData['serial_number'] : 1;

                        if (isset($arabicCategoriesBySerial[$blogSerial])) {

                            $blogData['category_id'] = $arabicCategoriesBySerial[$blogSerial]->id;
                        } else {
                            $blogData['category_id'] = reset($arabicCategoriesBySerial)->id;
                        }

                        \App\Models\User\Blog::create($blogData);
                    }
                }

                // --- Insert Home Page Texts ---
                $homePageTextsJson = <<<'JSON'
                [
                {
                    "about_image": "62381226ecd01.png",
                    "about_image_two": null,
                    "about_title": "حول رينجز",
                    "about_subtitle": "25 عاما من الخبرة في الحلول القانونية",
                    "about_content": "لكن لكي أفهم من أين وُلد كل هذا الخطأ ، سأفتح الأمر برمته في موقع تجول وآلام الناس المدح ، وسأشرح تلك الأشياء التي قالها مخترع الحقيقة والمهندس المعماري. من الحياة المباركة. فلا أحد يرفض المتعة نفسها لأنها متعة ، ولكن لأن الأشياء العظيمة تتبعها",
                    "about_button_text": "يتعلم أكثر",
                    "about_button_url": "http://example.com/",
                    "about_video_image": null,
                    "about_video_url": null,
                    "skills_title": null,
                    "skills_subtitle": null,
                    "skills_content": null,
                    "service_title": "خدمات الشركة",
                    "service_subtitle": "نحن نقدم خدمة حصرية",
                    "experience_title": null,
                    "experience_subtitle": null,
                    "portfolio_title": "حالات مميزة",
                    "portfolio_subtitle": "نلقي نظرة على الحالات",
                    "view_all_portfolio_text": "مشاهدة الكل",
                    "testimonial_title": "شهاداتنا",
                    "testimonial_subtitle": "يقول العملاء عنا",
                    "testimonial_image": "622ded84e62f8.jpg",
                    "blog_title": "أخبارنا ومدونتنا",
                    "blog_subtitle": "كل واحد التحديثات",
                    "view_all_blog_text": "مشاهدة الكل",
                    "team_section_title": "أعضاء الفريق",
                    "team_section_subtitle": "تعرف على خبرائنا المحترفين",
                    "video_section_image": null,
                    "video_section_url": null,
                    "video_section_title": null,
                    "video_section_subtitle": null,
                    "video_section_text": null,
                    "video_section_button_text": null,
                    "video_section_button_url": null,
                    "why_choose_us_section_image": "301b9239f5acc672e89ea19ccf4f7263207458394.jpg",
                    "why_choose_us_section_image_two": null,
                    "why_choose_us_section_title": "لماذا نحن الأفضل؟",
                    "why_choose_us_section_subtitle": "لدينا أسباب كثيرة لاختيارنا",
                    "why_choose_us_section_text": "لكنك ستفهم من أين يسعد كل هذا الخطأ المولود باتهام وألم أولئك الذين يمتدحونها ، وكل عمليات الاغتصاب التي هي من مخترع الحقيقة هذا وإن جاز التعبير.\r\nلكنك ستفهم من أين يسعد كل هذا الخطأ المولود بالاتهام والتصفيق",
                    "why_choose_us_section_button_text": "خدماتنا",
                    "why_choose_us_section_button_url": "http://example.com/",
                    "why_choose_us_section_video_image": "d1d67774227ae9d427fd1d391b578eb76c7ac1412.jpg",
                    "why_choose_us_section_video_url": "https://www.youtube.com/watch?v=pWOv9xcoMeY",
                    "faq_section_image": "6195e2a1d0dce3.png",
                    "faq_section_title": "التعليمات",
                    "faq_section_subtitle": "أسئلة مكررة",
                    "work_process_section_title": "كيف نعمل",
                    "work_process_section_subtitle": "عملية العمل لدينا",
                    "work_process_section_text": "",
                    "work_process_section_img": "00733bb91bb288918e16a40dfc1516839e550f91.jpg",
                    "work_process_section_video_img": null,
                    "work_process_section_video_url": null,
                    "quote_section_title": "إقتبس",
                    "quote_section_subtitle": "ولكن لمعرفة من الذي ولد كل هذا الخطأ sitevoluac",
                    "counter_section_image": "622df3492b4f1.jpg",
                    "work_process_btn_txt": "ابدأ مشروعًا",
                    "work_process_btn_url": "http://example.com/",
                    "contact_section_image": "63b41b3407c93.png",
                    "contact_section_title": "Requst a Quote",
                    "contact_section_subtitle": "Lorem ipsum dolor sit amet",
                    "feature_item_title": null,
                    "new_item_title": null,
                    "newsletter_title": null,
                    "newsletter_subtitle": null,
                    "bestseller_item_title": null,
                    "special_item_title": null,
                    "flashsale_item_title": null,
                    "toprated_item_title": null,
                    "category_section_title": null,
                    "category_section_subtitle": null,
                    "rooms_section_title": null,
                    "rooms_section_subtitle": null,
                    "rooms_section_content": null,
                    "featured_course_section_title": null,
                    "newsletter_image": null,
                    "featured_section_title": null,
                    "featured_section_subtitle": null,
                    "causes_section_title": null,
                    "causes_section_subtitle": null,
                    "about_snd_button_text": null,
                    "about_snd_button_url": null,
                    "skills_image": null,
                    "job_education_title": null,
                    "job_education_subtitle": null,
                    "newsletter_snd_image": null,
                    "donor_title": null,
                    "years_of_expricence": null,
                    "featured_property_title": null,
                    "property_title": null,
                    "city_title": null,
                    "city_subtitle": null,
                    "project_title": null,
                    "project_subtitle": null,
                    "testimonial_text": null
                },
                {
                    "about_image": "62381226ecd01.png",
                    "about_image_two": null,
                    "about_title": "About Us",
                    "about_subtitle": "Professional Business Guidance Agency",
                    "about_content": "Sedut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam raperiaeaque ipsa quae ab illo inventore veritatis et quasi\n\nDoloremque laudantium, totam raperiaeaque ipsa quae ab illo inventore veritatis et quasi\n\n Business &amp; Consulting Agency\n Awards Winning Business Comapny\n Business &amp; Consulting Agency\n Awards Winning Business Comapny",
                    "about_button_text": "Learn More",
                    "about_button_url": "http://example.com/",
                    "about_video_image": null,
                    "about_video_url": null,
                    "skills_title": null,
                    "skills_subtitle": null,
                    "skills_content": null,
                    "service_title": "Our Services",
                    "service_subtitle": "Lorem ipsum dolor sit amet consectetur e.",
                    "experience_title": null,
                    "experience_subtitle": null,
                    "portfolio_title": "Featured Cases",
                    "portfolio_subtitle": "Take a Look at the Cases",
                    "view_all_portfolio_text": "View All",
                    "testimonial_title": "Client’s Say",
                    "testimonial_subtitle": "Lorem ipsum dolor sit",
                    "testimonial_image": "6195e2885a64b.jpg",
                    "blog_title": "Our News and Blog",
                    "blog_subtitle": "Every Single Updates",
                    "view_all_blog_text": "View All",
                    "team_section_title": "Our Team",
                    "team_section_subtitle": "Lorem ipsum dolor sit amet",
                    "video_section_image": "4e075552eb76535027695b317dcc7cfed9e1e3cf.jpg",
                    "video_section_url": "https://www.youtube.com/watch?v=IjlYXtI2-GU",
                    "video_section_title": "Industrial Services That We Provide",
                    "video_section_subtitle": null,
                    "video_section_text": "Lorem ipsum dolor sit amet, consectetur adipi sicing Sed do eiusmod tempor incididunt labore et dolore magna aliqua. Ut enim ad minim veniam quis nostrud exercitation ullamco",
                    "video_section_button_text": null,
                    "video_section_button_url": null,
                    "why_choose_us_section_image": "301b9239f5acc672e89ea9ccf4f7263207458394.jpg",
                    "why_choose_us_section_image_two": null,
                    "why_choose_us_section_title": "Why We Are Best ?",
                    "why_choose_us_section_subtitle": "We Have Many Reasons to Choose Us",
                    "why_choose_us_section_text": "Sedut perspiciatis unde omnis iste natus error sit voluptat em accusantium doloremque laudantium, totam raperiaeaque ipsa quae ab illo inventore veritatis et quasi.\r\nSedut perspiciatis unde omnis iste natus error sit voluptat em accusantium doloremque laudantium, totam raperiaeaque ipsa quae ab illo inventore veritatis et quasi",
                    "why_choose_us_section_button_text": "Our Services",
                    "why_choose_us_section_button_url": "http://example.com/",
                    "why_choose_us_section_video_image": "d1d67774227ae9d427fdd391b578eb76c7ac1412.jpg",
                    "why_choose_us_section_video_url": "https://www.youtube.com/watch?v=pWOv9xcoMeY",
                    "faq_section_image": "6195e2ad0dce3.png",
                    "faq_section_title": "FAQ",
                    "faq_section_subtitle": "Frequently Asked Questions",
                    "work_process_section_title": "25 Years Of Experience",
                    "work_process_section_subtitle": "Best SEO Optimization Agency",
                    "work_process_section_text": "Lorem ipsum dolor sit amet, consectetur adipisicing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis",
                    "work_process_section_img": null,
                    "work_process_section_video_img": null,
                    "work_process_section_video_url": null,
                    "quote_section_title": "Start Work With us",
                    "quote_section_subtitle": "Lorem ipsum dolor sit amet",
                    "language_id": "",
                    "user_id": "",
                    "created_at": "2021-11-17 00:30:27",
                    "updated_at": "2024-11-03 20:14:04",
                    "counter_section_image": "622f3061a2073.jpg",
                    "work_process_btn_txt": "Learn More",
                    "work_process_btn_url": "http://example.com/",
                    "contact_section_image": "63b41b21c45a9.png",
                    "contact_section_title": "Requst a Quote",
                    "contact_section_subtitle": "Lorem ipsum dolor sit amet",
                    "feature_item_title": null,
                    "new_item_title": null,
                    "newsletter_title": null,
                    "newsletter_subtitle": null,
                    "bestseller_item_title": null,
                    "special_item_title": null,
                    "flashsale_item_title": null,
                    "toprated_item_title": null,
                    "category_section_title": null,
                    "category_section_subtitle": null,
                    "rooms_section_title": null,
                    "rooms_section_subtitle": null,
                    "rooms_section_content": null,
                    "featured_course_section_title": null,
                    "newsletter_image": null,
                    "featured_section_title": null,
                    "featured_section_subtitle": null,
                    "causes_section_title": null,
                    "causes_section_subtitle": null,
                    "about_snd_button_text": null,
                    "about_snd_button_url": null,
                    "skills_image": null,
                    "job_education_title": null,
                    "job_education_subtitle": null,
                    "newsletter_snd_image": null,
                    "donor_title": null,
                    "years_of_expricence": null,
                    "featured_property_title": null,
                    "property_title": null,
                    "city_title": null,
                    "city_subtitle": null,
                    "project_title": null,
                    "project_subtitle": null,
                    "testimonial_text": null
                }
                ]
                JSON;

                $homePageTextsArray = json_decode($homePageTextsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for home page texts: ' . json_last_error_msg());
                } else {
                    foreach ($homePageTextsArray as $textData) {
                        // Set the language and user IDs
                        $textData['language_id'] = $defaultLanguage->id;
                        $textData['user_id'] = $user->id;
                        \App\Models\User\HomePageText::create($textData);
                    }
                }

                // --- Insert Hero Sliders ---
                $heroSlidersJson = <<<'JSON'
                [
                {
                    "language_id": "",
                    "img": "784ffa3036c249fd132041bf56701406720e3e23.jpg",
                    "title": "Corporate Law Firms",
                    "subtitle": "25 Years Of Experience In Law Solutiuons",
                    "btn_name": "Our Services",
                    "btn_url": "http://example.com/",
                    "serial_number": "2",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:46"
                },
                {
                    "language_id": "",
                    "img": "37db1e96370fe3a98b1814d4fb6922822419bf3a.jpg",
                    "title": "Corporate Law Firms",
                    "subtitle": "25 Years Of Experience In Law Solutiuons",
                    "btn_name": "Our Services",
                    "btn_url": "http://example.com/",
                    "serial_number": "3",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:54"
                },
                {
                    "language_id": "",
                    "img": "9d5005c0ad6235fadbdec1e5f181c85f9cf51841.jpg",
                    "title": "شركات قانون الشركات",
                    "subtitle": "25 عاما من الخبرة في الحلول القانونية",
                    "btn_name": "خدماتنا",
                    "btn_url": "http://example.com/",
                    "serial_number": "1",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:12"
                },
                {
                    "language_id": "",
                    "img": "784ffa3036c249fd132041bf56701406720e3e23.jpg",
                    "title": "شركات قانون الشركات",
                    "subtitle": "25 عاما من الخبرة في الحلول القانونية",
                    "btn_name": "خدماتنا",
                    "btn_url": "http://example.com/",
                    "serial_number": "2",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:46"
                },
                {
                    "language_id": "",
                    "img": "37db1e96370fe3a98b1814d4fb6922822419bf3a.jpg",
                    "title": "شركات قانون الشركات",
                    "subtitle": "25 عاما من الخبرة في الحلول القانونية",
                    "btn_name": "خدماتنا",
                    "btn_url": "http://example.com/",
                    "serial_number": "3",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:54"
                }
                ]
                JSON;

                $heroSlidersArray = json_decode($heroSlidersJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for hero sliders: ' . json_last_error_msg());
                } else {
                    foreach ($heroSlidersArray as $sliderData) {
                        // Set the correct language and user IDs
                        $sliderData['language_id'] = $defaultLanguage->id;
                        $sliderData['user_id'] = $user->id;
                        \App\Models\User\HeroSlider::create($sliderData);
                    }
                }

                // --- Insert Socials ---
                $socialsJson = <<<'JSON'
                [
                {
                    "icon": "fab fa-facebook-f",
                    "url": "http://example.com/",
                    "serial_number": "1",
                    "user_id": "",
                    "created_at": "2021-11-17 06:34:10",
                    "updated_at": "2021-11-17 06:34:10"
                },
                {
                    "icon": "fab fa-twitter",
                    "url": "http://example.com/",
                    "serial_number": "2",
                    "user_id": "",
                    "created_at": "2021-11-17 06:34:18",
                    "updated_at": "2021-11-17 06:34:18"
                },
                {
                    "icon": "fab fa-linkedin-in",
                    "url": "http://example.com/",
                    "serial_number": "3",
                    "user_id": "",
                    "created_at": "2021-11-17 06:34:26",
                    "updated_at": "2021-11-17 06:34:26"
                },
                {
                    "icon": "fab fa-dribbble",
                    "url": "http://example.com/",
                    "serial_number": "4",
                    "user_id": "",
                    "created_at": "2021-11-17 06:34:48",
                    "updated_at": "2021-11-17 06:34:48"
                },
                {
                    "icon": "fab fa-behance",
                    "url": "http://example.com/",
                    "serial_number": "5",
                    "user_id": "",
                    "created_at": "2021-11-17 06:35:01",
                    "updated_at": "2021-11-17 06:35:01"
                }
                ]
                JSON;

                $socialsArray = json_decode($socialsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for socials: ' . json_last_error_msg());
                } else {
                    foreach ($socialsArray as $socialData) {
                        // Assign the current user's id to each record
                        $socialData['user_id'] = $user->id;
                        \App\Models\User\Social::create($socialData);
                    }
                }

                // --- Insert Testimonials ---
                $testimonialsJson = <<<'JSON'
                [
                {
                    "image": "1637126679.jpg",
                    "name": "Marco Veratti",
                    "occupation": "CEO, Janex",
                    "content": "It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here'",
                    "serial_number": "1",
                    "lang_id": "",
                    "user_id": "",
                    "created_at": "2021-11-14 19:51:53",
                    "updated_at": "2021-11-16 19:24:39"
                },
                {
                    "image": "1637127234.jpg",
                    "name": "Nicolo Zaniolo",
                    "occupation": "CTO, WebTech",
                    "content": "It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here'",
                    "serial_number": "2",
                    "lang_id": "",
                    "user_id": "",
                    "created_at": "2021-11-14 19:51:53",
                    "updated_at": "2021-11-16 19:33:54"
                },
                {
                    "image": "1637127252.jpg",
                    "name": "Adress Pirlo",
                    "occupation": "Manager, Madchef",
                    "content": "It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here'",
                    "serial_number": "3",
                    "lang_id": "",
                    "user_id": "",
                    "created_at": "2021-11-14 19:51:53",
                    "updated_at": "2021-11-16 19:34:12"
                },
                {
                    "image": "1637126679.jpg",
                    "name": "ماركو فيراتي",
                    "occupation": "الرئيس التنفيذي ، جانكس",
                    "content": "هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. الهدف من استخدام لوريم إيبسوم هو أنه يحتوي على توزيع طبيعي -إلى حد ما- للأحرف ، بدلاً من استخدام \"هنا يوجد محتوى نصي ، يوجد محتوى هنا\"",
                    "serial_number": "1",
                    "lang_id": "",
                    "user_id": "",
                    "created_at": "2021-11-14 19:51:53",
                    "updated_at": "2021-11-14 19:52:18"
                },
                {
                    "image": "1637127234.jpg",
                    "name": "نيكولو زانيولو",
                    "occupation": "CTO ، WebTech",
                    "content": "هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. الهدف من استخدام لوريم إيبسوم هو أنه يحتوي على توزيع طبيعي -إلى حد ما- للأحرف ، بدلاً من استخدام \"هنا يوجد محتوى نصي ، يوجد محتوى هنا\"",
                    "serial_number": "2",
                    "lang_id": "",
                    "user_id": "",
                    "created_at": "2021-11-14 19:51:53",
                    "updated_at": "2021-11-14 19:54:27"
                },
                {
                    "image": "1637127252.jpg",
                    "name": "العنوان بيرلو",
                    "occupation": "مدير ، Madchef",
                    "content": "هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. الهدف من استخدام لوريم إيبسوم هو أنه يحتوي على توزيع طبيعي -إلى حد ما- للأحرف ، بدلاً من استخدام \"هنا يوجد محتوى نصي ، يوجد محتوى هنا\"",
                    "serial_number": "3",
                    "lang_id": "",
                    "user_id": "",
                    "created_at": "2021-11-14 19:51:53",
                    "updated_at": "2021-11-14 19:54:38"
                }
                ]
                JSON;

                $testimonialsArray = json_decode($testimonialsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for testimonials: ' . json_last_error_msg());
                } else {
                    foreach ($testimonialsArray as $testimonialData) {
                        // Set the language and user IDs from your existing variables
                        $testimonialData['lang_id'] = $defaultLanguage->id;
                        $testimonialData['user_id'] = $user->id;
                        // Optionally remove timestamps if they're not in the fillable array
                        unset($testimonialData['created_at'], $testimonialData['updated_at']);
                        \App\Models\User\UserTestimonial::create($testimonialData);
                    }
                }

                // --- Insert Work Processes ---
                $workProcessJson = <<<'JSON'
                [
                    {
                        "icon": "far fa-bookmark",
                        "title": "Have A Coffee",
                        "text": "Doloremque laudantium totam raperiaeaqu ipsa quae ab illo inventore veritatis et quasi",
                        "serial_number": "1",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:09:36",
                        "updated_at": "2022-03-12 06:48:44"
                    },
                    {
                        "icon": "far fa-user",
                        "title": "Meet With Advisors",
                        "text": "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque",
                        "serial_number": "2",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:11:13",
                        "updated_at": "2021-11-16 19:11:13"
                    },
                    {
                        "icon": "fas fa-bullseye",
                        "title": "Achieve Your Goals",
                        "text": "Quis autem vel eum iure reprehenderit qui ieas voluptate velit esse quam nihil mole",
                        "serial_number": "3",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:12:07",
                        "updated_at": "2021-11-16 19:12:07"
                    },
                    {
                        "icon": "far fa-user",
                        "title": "Meet With Advisors",
                        "text": "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque",
                        "serial_number": "4",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:11:13",
                        "updated_at": "2021-11-16 19:11:13"
                    },
                    {
                        "icon": "fas fa-coffee",
                        "title": "تناول القهوة",
                        "text": "إن ألم أولئك الذين يثنون على كل شيء هو نفس الأشياء التي منه هو مخترع الحقيقة وإذا جاز التعبير.",
                        "serial_number": "1",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:09:36",
                        "updated_at": "2021-11-16 19:13:43"
                    },
                    {
                        "icon": "far fa-user",
                        "title": "لقاء مع المستشارين",
                        "text": "ولكن لكي نفهم من أين يولد كل هذا الخطأ ممن يتهمهم باللذة والألم",
                        "serial_number": "2",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:11:13",
                        "updated_at": "2021-11-16 19:11:13"
                    },
                    {
                        "icon": "fas fa-bullseye",
                        "title": "حقق اهدافك",
                        "text": "ولكن من يدين بحق من يريد أن تكون المتعة مجرد جماعية",
                        "serial_number": "3",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:12:07",
                        "updated_at": "2021-11-16 19:12:07"
                    },
                    {
                        "icon": "far fa-user",
                        "title": "لقاء مع المستشارين",
                        "text": "ولكن لكي نفهم من أين يولد كل هذا الخطأ ممن يتهمهم باللذة والألم",
                        "serial_number": "4",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:11:13",
                        "updated_at": "2021-11-16 19:11:13"
                    }
                ]
                JSON;

                $workProcessArray = json_decode($workProcessJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for work processes: ' . json_last_error_msg());
                } else {
                    foreach ($workProcessArray as $workProcessData) {
                        // Set the current language and user IDs
                        $workProcessData['language_id'] = $defaultLanguage->id;
                        $workProcessData['user_id'] = $user->id;
                        // Remove extra keys that are not fillable
                        unset($workProcessData['created_at'], $workProcessData['updated_at']);
                        \App\Models\User\WorkProcess::create($workProcessData);
                    }
                }

                // --- Begin: Insert Property Categories Records into user_property_categories ---
                $propertyCategoriesJson = <<<'JSON'
                [
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "residential",
                        "name": "شقة",
                        "slug": "شقة",
                        "image": "67be66fe9fa44.jpg",
                        "status": "1",
                        "featured": "0",
                        "serial_number": "0",
                        "created_at": "2025-02-03 13:51:00",
                        "updated_at": "2025-02-26 03:57:34"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "residential",
                        "name": "دور",
                        "slug": "دور",
                        "image": "67a0add555128.jpg",
                        "status": "1",
                        "featured": "0",
                        "serial_number": "1",
                        "created_at": "2025-02-03 13:51:49",
                        "updated_at": "2025-02-03 13:38:29"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "residential",
                        "name": "فيلا",
                        "slug": "فيلا",
                        "image": "67a0adfc6b72b.jpg",
                        "status": "1",
                        "featured": "0",
                        "serial_number": "2",
                        "created_at": "2025-02-03 13:52:28",
                        "updated_at": "2025-02-03 13:38:32"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "commercial",
                        "name": "ارض",
                        "slug": "ارض",
                        "image": "67a0c6fc91f80.png",
                        "status": "1",
                        "featured": "1",
                        "serial_number": "3",
                        "created_at": "2025-02-03 13:39:08",
                        "updated_at": "2025-02-03 13:39:21"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "residential",
                        "name": "دوبلكس",
                        "slug": "دوبلكس",
                        "image": "67be671e3439b.jpg",
                        "status": "1",
                        "featured": "1",
                        "serial_number": "4",
                        "created_at": "2025-02-03 13:39:46",
                        "updated_at": "2025-02-26 03:58:06"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "commercial",
                        "name": "تاون هاوس",
                        "slug": "تاون-هاوس",
                        "image": "67a0c73cc5b90.png",
                        "status": "1",
                        "featured": "1",
                        "serial_number": "5",
                        "created_at": "2025-02-03 13:40:12",
                        "updated_at": "2025-02-03 13:41:12"
                    }
                ]
                JSON;

                $propertyCategoriesArray = json_decode($propertyCategoriesJson, true);

                foreach ($propertyCategoriesArray as $catData) {
                    // Insert category for Default Language (Arabic)
                    \App\Models\User\RealestateManagement\Category::create([
                        'user_id' => $user->id,
                        'language_id' => $defaultLanguage->id,
                        'type' => $catData['type'],
                        'name' => $catData['name'], // Arabic Name
                        'slug' => $catData['slug'], // Arabic Slug
                        'image' => $catData['image'],
                        'status' => $catData['status'],
                        'featured' => $catData['featured'],
                        'serial_number' => $catData['serial_number']
                    ]);

                    // Insert category for Secondary Language (English)
                    // \App\Models\User\RealestateManagement\Category::create([
                    //     'user_id' => $user->id,
                    //     'language_id' => $secondLanguage->id,
                    //     'type' => $catData['type'],
                    //     'name' => $catData['name'], // Keeping same name for now
                    //     'slug' => $catData['slug'], // Keeping same slug for now
                    //     'image' => $catData['image'],
                    //     'status' => $catData['status'],
                    //     'featured' => $catData['featured'],
                    //     'serial_number' => $catData['serial_number']
                    // ]);
                }

                // --- End: Insert Property Categories Records into user_property_categories ---




                //
                //
                //
                //


                // --- email verification ---
                $ubs = BasicSetting::select('email_verification_status')->first();

                if ($ubs->email_verification_status == 1) {
                    $mailer = new MegaMailer();
                    $data = [
                        'toMail' => $user->email,
                        'toName' => $user->first_name,
                        'customer_name' => $user->first_name,
                        'verification_link' => $verification_link,
                        'website_title' => $bs->website_title,
                        'templateType' => 'email_verification',
                        'type' => 'emailVerification'
                    ];
                    $mailer->mailFromAdmin($data);
                }

                $package = Package::findOrFail($request['package_id']);
                if (is_array($request)) {
                    $conversation_id = array_key_exists('conversation_id', $request) ? $request['conversation_id'] : null;
                } else {
                    $conversation_id = null;
                }

                Membership::create([
                    'package_price' => $package->price,
                    'discount' => session()->has('coupon_amount') ? session()->get('coupon_amount') : 0,
                    'coupon_code' => session()->has('coupon') ? session()->get('coupon') : NULL,
                    'price' => $amount,
                    'currency' => $be->base_currency_text ? $be->base_currency_text : "USD",
                    'currency_symbol' => $be->base_currency_symbol ? $be->base_currency_symbol : $be->base_currency_text,
                    'payment_method' => $request["payment_method"],
                    'transaction_id' => $transaction_id ? $transaction_id : 0,
                    'status' => $request["status"] ? $request["status"] : 0,
                    'is_trial' => $request["package_type"] == "regular" ? 0 : 1,
                    'trial_days' => $request["package_type"] == "regular" ? 0 : $request["trial_days"],
                    'receipt' => $request["receipt_name"] ? $request["receipt_name"] : null,
                    'transaction_details' => $transaction_details ? $transaction_details : null,
                    'settings' => json_encode($be),
                    'package_id' => $request['package_id'],
                    'user_id' => $user->id,
                    'start_date' => Carbon::parse($request['start_date']),
                    'expire_date' => Carbon::parse($request['expire_date']),
                    'conversation_id' => $conversation_id
                ]);

                $features = json_decode($package->features, true);
                $features[] = "Contact";
                UserPermission::create([
                    'package_id' => $request['package_id'],
                    'user_id' => $user->id,
                    'permissions' => json_encode($features)
                ]);

                $payment_keywords = ['flutterwave', 'razorpay', 'paytm', 'paystack', 'instamojo', 'stripe', 'paypal', 'mollie', 'mercadopago', 'authorize.net', 'phonepe'];
                foreach ($payment_keywords as $key => $value) {
                    UserPaymentGeteway::create([
                        'title' => null,
                        'user_id' => $user->id,
                        'details' => null,
                        'keyword' => $value,
                        'subtitle' => null,
                        'name' => ucfirst($value),
                        'type' => 'automatic',
                        'information' => null
                    ]);
                }

                $templates = ['email_verification', 'product_order', 'reset_password', 'room_booking', 'room_booking', 'payment_received', 'payment_cancelled', 'course_enrolment', 'course_enrolment_approved', 'course_enrolment_rejected', 'donation', 'donation_approved'];
                foreach ($templates as $key => $val) {
                    UserEmailTemplate::create([
                        'user_id' => $user->id,
                        'email_type' => $val,
                        'email_subject' => null,
                        'email_body' => '<p></p>',
                    ]);
                }

                $homeSection = new HomeSection();
                $homeSection->user_id = $user->id;
                $homeSection->save();

                UserShopSetting::create([
                    'user_id' => $user->id,
                    'is_shop' => 1,
                    'catalog_mode' => 0,
                    'item_rating_system' => 1,
                    'tax' => 0,
                ]);
            }

            if (Session::has('coupon')) {
                $coupon = Coupon::where('code', Session::get('coupon'))->first();
                $coupon->total_uses = $coupon->total_uses + 1;
                $coupon->save();
            }

            return $user;
        });
    }

    public function onlineSuccess()
    {
        Session::forget('coupon');
        Session::forget('coupon_amount');
        return view('front.success');
    }

    public function offlineSuccess()
    {
        Session::forget('coupon');
        Session::forget('coupon_amount');
        return view('front.offline-success');
    }

    public function trialSuccess()
    {
        Session::forget('coupon');
        Session::forget('coupon_amount');
        return view('front.trial-success');
    }

    public function coupon(Request $request)
    {
        if (session()->has('coupon')) {
            return 'Coupon already applied';
        }
        $coupon = Coupon::where('code', $request->coupon)->first();
        if (empty($coupon)) {
            return 'This coupon does not exist';
        }
        $coupon_count = $coupon->total_uses;
        if ($coupon->maximum_uses_limit != 999999) {
            if ($coupon_count >= $coupon->maximum_uses_limit) {
                return 'This coupon reached maximum limit';
            }
        }
        $start = Carbon::parse($coupon->start_date);
        $end = Carbon::parse($coupon->end_date);
        $today = Carbon::parse(Carbon::now()->format('m/d/Y'));
        $packages = $coupon->packages;
        $packages = json_decode($packages, true);
        $packages = !empty($packages) ? $packages : [];
        if (!in_array($request->package_id, $packages)) {
            return 'This coupon is not valid for this package';
        }

        if ($today->greaterThanOrEqualTo($start) && $today->lessThanOrEqualTo($end)) {
            $package = Package::find($request->package_id);
            $price = $package->price;
            if ($coupon->type == 'percentage') {
                $cAmount = ($price * $coupon->value) / 100;
            } else {
                $cAmount = $coupon->value;
            }

            Session::put('coupon', $request->coupon);
            Session::put('coupon_amount', round($cAmount, 2));
            return "success";
        } else {
            return 'This coupon does not exist';
        }
    }
}

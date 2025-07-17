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
use Carbon\Carbon;
use App\Http\Helpers\MegaMailer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\BasicExtended;
use App\Models\BasicSetting;
use App\Models\User\BasicSetting as UserBasicSetting;

class ArbController extends Controller
{
    protected array $data = [];
    public $arb;

    public function __construct()
    {
        $currentLang = session()->has('lang') ?
            (Language::where('code', session()->get('lang'))->first())
            : (Language::where('is_default', 1)->first());
        $be = $currentLang->basic_extended;

        $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();
        $paydata = $paymentMethod->convertAutoData();
    }

    public function paymentProcess(Request $request, $_amount, $_success_url, $_cancel_url, $_title,$user_id,string $context = 'MEMBERSHIP', int $app_id  = 0)
    {
        /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Buy Plan Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
       // Session::put('request', $request->all());

        /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Payment Gateway Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
        $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();
        $paydata = $paymentMethod->convertAutoData();


        $random_1 = rand(999, 9999);
        $random_2 = rand(9999, 99999);
        $paymentFor = 'membership';
        $name = $paymentFor == 'membership' ? $request->first_name . ' ' . $request->last_name : auth()->user()->first_name . ' ' . auth()->user()->first_name;

        $phone = $paymentFor == 'membership' ? $request->phone : auth()->user()->phone;

        // $package_id_request = (int)$request->package_id;
        $package_id_request = (int) ($request->package_id ?? 0);

       // $package = Package::find($package_id_request); // Replace 1 with your package ID
        // $package = Package::where('id', $package_id_request)->first();
        $package = $package_id_request ? Package::find($package_id_request) : null;
        // \Log::info('debug ' . $_amount);
        $price = $_amount;
        if ($package) {
            // Membership flow
            $price       = $_amount;
            $paymentFor  = 'membership';
            $name        = $name;
            $phone       = $phone;
        } else {
            // App-purchase flow
            $price       = (float) $_amount;          // ← use the param we passed in
            $paymentFor  = 'app';
            $name        = auth()->user()->name ?? 'Unknown';
            $phone       = auth()->user()->phone ?? '';
        }
        // if ($package) {

        // } else {
        //     return 'error';
        // }

        //#######################################################################
        $trackId = uniqid($price * time());
        $data = [
            'id'          => $paydata['tranportal_id'],
            'password'    => $paydata['tranportal_password'],
            'action'      => '1',
            'trackId'     => $trackId,
            'amt'         => (float) $price,
            'currencyCode'=> '682',
            'langid'      => 'ar',
            'responseURL' => $_success_url,
            'errorURL'    => $_cancel_url,
            'udf1'        => $package_id_request,
            'udf2'        => $user_id,
            'udf3'        => $context,
            'udf4'        => $app_id,
        ];

       // $data = $data + $this->generateUdfs($data);
        // log::info($data);
        $data = $this->createRequestBody($this->wrapData($data));

        // log::info($data);

        $configName = 'bank_hosted_endpoint';

        $response = Http::withBody($data, 'application/json')
            ->withOptions(['verify' => false])
            ->post($paydata["mode"] == 'live'
            ? $paydata["live_$configName"]
            : $paydata["test_$configName"]
            );


        $response = $response->json('0');

        if ($response['status'] == '1') {
            [$paymentID, , $baseURL] = explode(':', $response['result']);
            $baseURL = 'https:'.$baseURL;
            $paymentID = '?PaymentID='.$paymentID;
            $return_object = array(
                'redirect_url'=> $baseURL.$paymentID,
                'payment_token' => $paymentID
            );

            return $return_object;
          //  return redirect($baseURL.$paymentID);
        } else {
            return 'error';
          //  return redirect(route('membership.arb.cancel'));
        }
    }

    public function paymentProcessForApp(\App\Models\User $user, \App\Models\Api\ApiApp $app): array
    {
        $dummyReq = new \Illuminate\Http\Request([
            'first_name' => $user->name,
            'last_name'  => '',
            'phone'      => $user->phone ?? '',
            'package_id' => 0,
        ]);

        return $this->paymentProcess(
            $dummyReq,
            $app->price,
            route('mf.app.success', [], true),
            route('mf.app.cancel',  [], true),
            "شراء تطبيق {$app->name}",
            $user->id,
            'APP',
            $app->id
        );

    }

    // return to success page

    public function failedPayment(Request $request)
    {
        return redirect()->route('failed.page');
    }
    public function successPayment(Request $request)
    {

        $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();
        $paydata = $paymentMethod->convertAutoData();

        $currentLang = session()->has('lang') ?
            (Language::where('code', session()->get('lang'))->first())
            : (Language::where('is_default', 1)->first());
        $bs = $currentLang->basic_setting;
        $be = $currentLang->basic_extended;

        $dataArr = json_decode($request, true);

        $decrypted = $this->decryption($request['trandata'], $paydata["resource_key"]);


        $raw = urldecode($decrypted);
        $dataArr = json_decode($raw, true);

        log::info($dataArr);
        if (!empty($dataArr) && is_array($dataArr)) {
            $paymentData = $dataArr[0]; // Get the first element

            if (isset($paymentData['result']) && $paymentData['result'] === 'CAPTURED') {
                $isSuccessful = true;
                $resultMessage = 'payment_success';
                $package_id = $paymentData['udf1'];
                $user_id = $paymentData['udf2'];
                $price = $paymentData['amt'];


                // You can access transaction details like $paymentData['transId'], $paymentData['amt'], etc.
            } else if (isset($paymentData['error'])) {
                $isSuccessful = false;
                $resultMessage = 'payment_failed';
            } else {
                $isSuccessful = false;
                $resultMessage = 'payment_failed';
            }
        }

        // Now you can use $isSuccessful and $resultMessage as needed
        if ($isSuccessful) {

            $user = User::findOrFail($user_id);
            log::info($user);
            $currMembership = UserPermissionHelper::currMembOrPending($user_id);
            $nextMembership = UserPermissionHelper::nextMembership($user_id);

            $be = BasicExtended::first();
            $bs = BasicSetting::select('website_title')->first();

            $selectedPackage = Package::find($package_id);

            // if the user has a next package to activate & selected package is 'lifetime' package
            // if (!empty($nextMembership) && $selectedPackage->term == 'lifetime') {
            //     Session::flash('membership_warning', 'To add a Lifetime package as Current Package, You have to remove the next package');
            //     return back();
            // }

            // expire the current package
            log::info('ddd'.$currMembership);
            $currMembership->expire_date = Carbon::parse(Carbon::now()->subDay()->format('d-m-Y'));
            $currMembership->modified = 1;
            if ($currMembership->status == 0) {
                $currMembership->status = 2;
            }
            $currMembership->save();

            // calculate expire date for selected package
            if ($selectedPackage->term == 'monthly') {
                $exDate = Carbon::now()->addMonth()->format('d-m-Y');
            } elseif ($selectedPackage->term == 'yearly') {
                $exDate = Carbon::now()->addYear()->format('d-m-Y');
            } elseif ($selectedPackage->term == 'lifetime') {
                $exDate = Carbon::maxValue()->format('d-m-Y');
            }

            $requestData = [
                'user_id' => $user_id,
                'start_date' => Carbon::parse(Carbon::now()->format('d-m-Y')),
                'price' => $price,
                'package_id' => $package_id,
                'payment_method' => 'Arb',
                'status' => 1,
                'receipt_name' => 'Monthly Subscription',
                'expire_date' => Carbon::parse($exDate),
            ];

            $paymentFor = 'extend';
            $package = Package::find($package_id);
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = '';
            // update user subscribed
            $user->subscribed = true;
            $user->subscription_amount = $package->price;
            $user->save();

            if ($user->referred_by) {
                $affiliate = \App\Models\Api\ApiAffiliateUser::where('user_id', $user->referred_by)->first();

                if ($affiliate) {
                    $commissionRate = $affiliate->commission_percentage ?? 0.15;
                    $commission = round($package->price * $commissionRate, 2);

                    // Update affiliate total commission
                    $affiliate->pending_amount += $commission;
                    $affiliate->save();

                    // create transaction as pending
                    \App\Models\AffiliateTransaction::create([
                        'affiliate_id' => $affiliate->id,
                        'type'         => 'pending', // will require admin approval
                        'referral_user_id' => $user->id, // Link to the user who made the payment
                        'image'        => null,
                        'amount'       => $commission,
                        'note'         => "commission from username: ({$user->name}) for package: ({$package->title})",
                    ]);
                }
            }

            if ($paymentFor == "membership") {
                $amount = $price;
                $password = $requestData['password'];
                $checkout = new CheckoutController();
                $user = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $be, $password);

                $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
                $activation = Carbon::parse($lastMemb->start_date);
                $expire = Carbon::parse($lastMemb->expire_date);
              //  $file_name = $this->makeInvoice($requestData, "membership", $user, $password, $amount, $requestData["payment_method"], $requestData['phone'], $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id, $package->title, $lastMemb);

                return redirect(route('customer.dashboard'));

            } elseif ($paymentFor == "extend") {
                $amount = $price;
                $password = uniqid('qrcode');
                $checkout = new UserCheckoutController();
                $user = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $be, $password);

                $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
                $activation = Carbon::parse($lastMemb->start_date);
                $expire = Carbon::parse($lastMemb->expire_date);
              // $file_name = $this->makeInvoice($requestData, "extend", $user, $password, $amount, $requestData["payment_method"], $user->phone, $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id, $package->title, $lastMemb);
            }

            return redirect()->route('success.page');
        } else {

            return redirect()->route('failed.page');
        }

       // log::info('data back'.$request);



    }

    private function decryption($code, $key): false|string
    {
        $string = hex2bin(trim($code));
        $code = unpack('C*', $string);
        $chars = array_map('chr', $code);
        $code = implode($chars);
        $code = base64_encode($code);
        $decrypted = openssl_decrypt($code, 'AES-256-CBC', $key, OPENSSL_ZERO_PADDING, 'PGKEYENCDECIVSPC');
        $pad = ord($decrypted[strlen($decrypted) - 1]);
        if ($pad > strlen($decrypted)) {
            return false;
        }
        if (strspn($decrypted, chr($pad), strlen($decrypted) - $pad) != $pad) {
            return false;
        }

        return urldecode(substr($decrypted, 0, -1 * $pad));
    }

    private function encryption(string $str, string $key): string
    {
        $blocksize = openssl_cipher_iv_length('AES-256-CBC');
        $pad = $blocksize - (strlen($str) % $blocksize);
        $str = $str.str_repeat(chr($pad), $pad);
        $encrypted = openssl_encrypt($str, 'AES-256-CBC', $key, OPENSSL_ZERO_PADDING, 'PGKEYENCDECIVSPC');
        $encrypted = base64_decode($encrypted);
        $encrypted = unpack('C*', ($encrypted));
        $chars = array_map('chr', $encrypted);
        $bin = implode($chars);
        $encrypted = bin2hex($bin);

        return urlencode($encrypted);
    }

    private function wrapData(array $data): string
    {
        $data = json_encode($data);

        return "[$data]";
    }

    private function createRequestBody($encoded_data): string
    {
        $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();
        $paydata = $paymentMethod->convertAutoData();

        $encryptedData = [
            'id' => $paydata['tranportal_id'],
            'trandata' => $this->encryption($encoded_data, $paydata['resource_key']),
            'responseURL' => route('membership.arb.success'),
            'errorURL' => route('membership.arb.cancel'),
        ];

        return $this->wrapData($encryptedData);
    }
    public function generateUdfs($data)
    {
        $maxChar = 255;
        $maxudfs = 5;
        $str = base64_encode(json_encode($data));
        // split the string into chunks of 255 characters
        $chunks = str_split($str, $maxChar);
        if (count($chunks) > $maxudfs) {
            throw new \Exception('Data is too large to be sent');
        }

        $udfs = [];
        foreach ($chunks as $key => $chunk) {
            $udfs["udf".($key + 1)] = $chunk;
        }
        return $udfs;
    }

    public function handlePaymentRequest(string $data): object
    {


    }

}

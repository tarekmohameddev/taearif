<?php

namespace App\Http\Controllers\CRM;


use App\Constants\Constant;
use Config;
use User\HomePageText;
use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Helpers\Uploader;
use App\Models\User\UserOrder;
use App\Models\User\UserContact;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\User\CourseManagement\Course;
use App\Models\User\CourseManagement\CourseEnrolment;
use App\Models\User\CourseManagement\CourseInformation;
use App\Models\User\CourseManagement\Lesson;
use App\Models\User\CourseManagement\LessonComplete;
use App\Models\User\CourseManagement\LessonContent;
use App\Models\User\CourseManagement\LessonContentComplete;
use App\Models\User\CourseManagement\LessonQuiz;
use App\Models\User\CourseManagement\Module;
use App\Models\User\CourseManagement\QuizScore;
use App\Models\User\UserShopSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User\CustomerWishList;
use App\Models\User\DonationManagement\DonationContent;
use App\Models\User\DonationManagement\DonationDetail;
use App\Models\User\HomePageText as UserHomePageText;
use App\Models\User\HotelBooking\RoomBooking;
use App\Models\User\RealestateManagement\PropertyWishlist;
use App\Models\User\UserEmailTemplate;
use App\Models\User\UserOfflineGateway;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

// use App\Models\Customer;
// use Illuminate\Http\Request;
// use App\Http\Controllers\Controller;

class CustcrmomerController extends Controller
{

    // for crm show  customers
    public function customers()
    {

        // customers
        // $customers = Customer::orderBy('id', 'DESC')->get();
        // create  pagnation 10 per page
        $customers = Customer::orderBy('id', 'DESC')->paginate(10);

        // dd($customers);
        return view('real-estate.back.customers.index', compact('customers'));

    }
    //
    // for create new customer
    public function createCustomer()
    {
        $user = Auth::user()->username;

        return view('real-estate.back.customers.create', compact('user'));
    }

    // for store new customer
    public function storeCustomer(Request $request)
    {
        $user = Auth::user();
        $messages = [];
        $rules = [];
        $rules = [
            'username' => [
                'required',
                'max:255',
                function ($attribute, $value, $fail) use ($user) {
                    if (Customer::where('username', $value)->where('user_id', $user->id)->count() > 0) {
                        $fail('Username has already been taken');
                    }
                }
            ],
            'email' => ['required', 'email', 'max:255', function ($attribute, $value, $fail) use ($user) {
                if (Customer::where('email', $value)->where('user_id', $user->id)->count() > 0) {
                    $fail('Email has already been taken');
                }
            }],
            'password' => 'required|confirmed',
            'password_confirmation' => 'required'
        ];

        $ubs  = BasicSetting::where('user_id', Auth::user()->id)->first();
        if ($ubs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
            $messages = [
                'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
                'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
            ];
        }

        $request->validate($rules, $messages);


        $customer = new Customer;
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->contact_number = $request->contact_number;
        $customer->address = $request->address;
        $customer->status = $request->status;
        //
        $customer->username = $request->username;
        $customer->email = $request->email;
        $customer->user_id = $user->id;
        $customer->password = Hash::make($request->password);
        // first, generate a random string
        $randStr = Str::random(20);
        // second, generate a token
        $token = md5($randStr . $request->username . $request->email);
        $customer->verification_token = $token;
        $customer->save();
        // send a mail to user for verify his/her email address
        if ($ubs->email_verification_status == 1) {
            $this->sendVerificationMail($request, $token);
            $message = ['sendmail' => 'We need to verify your email address. We have sent an email to  ' . $request->email . ' to verify your email address. Please click link in that email to continue.'];
        } else {
            $message = [];
        }

        return redirect()
            ->back()
            ->with($message);

        // return redirect()->route('crm.customers')->with('success', 'Customer created successfully')->with($message);
    }
    public function sendVerificationMail(Request $request, $token)
    {


        $user = Auth::user();

        // first get the mail template information from db
        $mailTemplate = UserEmailTemplate::where('user_id', $user->id)->where('email_type', 'email_verification')->first();

        $mailSubject = $mailTemplate->email_subject;
        $mailBody = $mailTemplate->email_body;
        // second get the website title & mail's smtp information from db
        $info = DB::table('basic_extendeds')
            ->select('is_smtp', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name')
            ->first();

        $websiteInfo = BasicSetting::where('user_id', $user->id)->select('website_title')->first();
        $link = '<a href=' . route('customer.signup.verify', ['token' => $token, getParam()]) . '>Click Here</a>';
        // replace template's curly-brace string with actual data
        $mailBody = str_replace('{customer_name}', $request->username, $mailBody);
        $mailBody = str_replace('{verification_link}', $link, $mailBody);
        $mailBody = str_replace('{website_title}', $websiteInfo->website_title, $mailBody);
        $userInfo = BasicSetting::where('user_id', $user->id)->select('email', 'from_name')->first();

        $email = $userInfo->email ?? $user->email;
        $name = $userInfo->from_name ?? $user->username;

        // initialize a new mail
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";
        // if smtp status == 1, then set some value for PHPMailer
        if ($info->is_smtp == 1) {
            $mail->isSMTP();
            $mail->Host       = $info->smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $info->smtp_username;
            $mail->Password   = $info->smtp_password;
            if ($info->encryption == 'TLS') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port = $info->smtp_port;
        }
        // finally, add other information and send the mail
        // try {
        //     $mail->setFrom($info->from_mail, $name);
        //     $mail->addReplyTo($email);
        //     $mail->addAddress($request->email);
        //     $mail->isHTML(true);
        //     $mail->Subject = $mailSubject;
        //     $mail->Body = $mailBody;
        //     $mail->send();
        //     $request->session()->flash('success', 'A verification mail has been sent to your email address.');
        // } catch (\Exception $e) {
        //     dd($e);
        //     $request->session()->flash('error', 'Mail could not be sent!');
        // }
        return;
    }

    // for edit customer
    public function editCustomer(Request $request,$id)
    {
        // dd($id);
        $customer = Customer::findOrFail($id);
        // dd($customer);
        return view('real-estate.back.customers.edit', compact('customer'));
    }

    // for update customer
    public function updateCustomer(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|unique:customers,email,' . $request->id,
            'contact_number' => 'required',
            'address' => 'required',
            'status' => 'required',
        ]);

        $customer = Customer::findOrFail($request->id);
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->email = $request->email;
        $customer->contact_number = $request->contact_number;
        $customer->address = $request->address;
        $customer->status = $request->status;
        $customer->save();

        return redirect()->route('crm.customers')->with('success', 'Customer updated successfully');
    }

    // for delete customer
    public function deleteCustomer($id)
    {
        // dd($id);
        $customer = Customer::findOrFail($id);
        $customer->delete();
        return redirect()->route('crm.customers')->with('success', 'Customer deleted successfully');
    }

    //

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function destroy($id)
    // {
    //     //
    // }
}

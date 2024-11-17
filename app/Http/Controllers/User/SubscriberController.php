<?php

namespace App\Http\Controllers\User;

use Mail;
use Session;
use App\Mail\ContactMail;
use Illuminate\Http\Request;
use App\Models\BasicExtended;
use PHPMailer\PHPMailer\SMTP;
use App\Models\User\Subscriber;
use App\Models\User\BasicSetting;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->term;
        $data['subscs'] = Subscriber::where('user_id', Auth::id())
            ->when($term, function ($query, $term) {
                return $query->where('email', 'LIKE', '%' . $term . '%');
            })->orderBy('id', 'DESC')->paginate(10);
        return view('user.subscribers.index', $data);
    }

    public function store(Request $request, $domain)
    {
        $user = getUser();
        $dd = $request->validate([
            'email' => [
                'required',
                function ($attribute, $value, $fail) use ($user) {
                    $subscriber = Subscriber::where([
                        ['email', $value],
                        ['user_id', $user->id]
                    ])->get();
                    if ($subscriber->count() > 0) {
                        Session::flash('error', 'This email is already subscribed');
                        $fail(':attribute already subscribed for this user');
                    }
                },
            ],
            'g-recaptcha-response' => [Rule::requiredIf(function () use ($user) {
                $ubs  = BasicSetting::where('user_id', $user->id)->first();
                return $ubs->is_recaptcha == 1;
            }), 'captcha']
        ], [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
            'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
        ]);
        $request['user_id'] = $user->id;
        Subscriber::create($request->all());
        Session::flash('success', 'You subscribed successfully!');
        return back();
    }

    public function mailsubscriber()
    {
        return view('user.subscribers.mail');
    }

    public function getMailInformation()
    {
        $data['info'] = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->select('email', 'from_name')->first();
        return view('user.subscribers.mail-information', $data);
    }

    public function storeMailInformation(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'from_name' => 'required'
        ], [
            'email.required' => 'The email field is required',
            'from_name.required' => 'The from name field is required'
        ]);
        $info = \App\Models\User\BasicSetting::where('user_id', Auth::id())->first();
        $info->email = $request->email;
        $info->from_name = $request->from_name;
        $info->save();
        Session::flash('success', 'Mail information saved successfully!');
        return back();
    }

    public function subscsendmail(Request $request)
    {
        $request->validate([
            'subject' => 'required',
            'message' => 'required'
        ]);

        $sub = $request->subject;
        $msg = $request->message;

        $subscs = Subscriber::where('user_id', Auth::id())->get();
        $info = \App\Models\User\BasicSetting::where('user_id', Auth::id())->select('email', 'from_name')->first();
        $email = $info->email ?? Auth::user()->email;
        $name = $info->from_name ?? Auth::user()->company_name;
        $settings = BasicSetting::first();
        $from = $settings->contact_mail;

        $be = BasicExtended::first();

        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8"; 
        if ($be->is_smtp == 1) {
            try {
                //Server settings
                $mail->isSMTP();                                            // Send using SMTP
                $mail->Host = $be->smtp_host;                    // Set the SMTP server to send through
                $mail->SMTPAuth = true;                                   // Enable SMTP authentication
                $mail->Username = $be->smtp_username;                     // SMTP username
                $mail->Password = $be->smtp_password;                               // SMTP password
                $mail->SMTPSecure = $be->encryption;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                $mail->Port = $be->smtp_port;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
                $mail->addReplyTo($email);

                //Recipients
                $mail->setFrom($be->from_mail, $name);

                foreach ($subscs as $key => $subsc) {
                    $mail->addBCC($subsc->email);
                }
            } catch (Exception $e) {
            }
        } else {
            try {
                //Recipients
                $mail->setFrom($be->from_mail, $name);
                $mail->addReplyTo($email);
                foreach ($subscs as $key => $subsc) {
                    $mail->addBCC($subsc->email);
                }
            } catch (Exception $e) {
            }
        }
        // Content
        $mail->isHTML(true);   // Set email format to HTML
        $mail->Subject = $sub;
        $mail->Body = $msg;

        $mail->send();

        Session::flash('success', 'Mail sent successfully!');
        return back();
    }


    public function delete(Request $request)
    {
        Subscriber::findOrFail($request->subscriber_id)->delete();
        Session::flash('success', 'Subscriber deleted successfully!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            Subscriber::findOrFail($id)->delete();
        }
        Session::flash('success', 'Subscribers deleted successfully!');
        return "success";
    }
}

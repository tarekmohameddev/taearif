<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BasicExtended;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class EmailCommunicationController extends Controller
{
    public function __construct()
    {
        $abs = BasicExtended::first();
        if ($abs) {
            config(['app.timezone' => $abs->timezone]);
        }
    }

    public function index()
    {
        $data['abs'] = BasicExtended::first();
        return view('admin.communication.email.index', $data);
    }

    public function updateSmtpSettings(Request $request)
    {
        $rules = [
            'is_smtp' => 'nullable|boolean',
            'smtp_host' => 'required_if:is_smtp,1|string|max:255',
            'smtp_port' => 'required_if:is_smtp,1|string|max:10',
            'smtp_username' => 'required_if:is_smtp,1|string|max:255',
            'smtp_password' => 'required_if:is_smtp,1|string|max:255',
            'encryption' => 'required_if:is_smtp,1|string|in:TLS,SSL',
            'from_mail' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicExtended::first();
        if (!$abs) {
            $abs = new BasicExtended();
        }

        $abs->is_smtp = $request->has('is_smtp') ? 1 : 0;
        $abs->smtp_host = $request->smtp_host;
        $abs->smtp_port = $request->smtp_port;
        $abs->smtp_username = $request->smtp_username;
        $abs->smtp_password = $request->smtp_password;
        $abs->encryption = $request->encryption;
        $abs->from_mail = $request->from_mail;
        $abs->from_name = $request->from_name;
        $abs->save();

        Session::flash('success', 'SMTP settings updated successfully!');
        return redirect()->back();
    }

    public function updateEmailTemplates(Request $request)
    {
        $rules = [
            'email_password_reset_template' => 'nullable|string|max:100',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicExtended::first();
        if (!$abs) {
            $abs = new BasicExtended();
        }

        $abs->email_password_reset_template = $request->email_password_reset_template;
        $abs->save();

        Session::flash('success', 'Email template settings updated successfully!');
        return redirect()->back();
    }

    public function testEmailConfiguration(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            $emailService = new EmailService();
            $success = $emailService->testEmailConfiguration($request->test_email);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . $request->test_email
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test email. Please check SMTP configuration.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}

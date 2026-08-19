<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Package;
use App\Models\Language;
use App\Models\Membership;
use App\Models\BasicSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Helpers\MegaMailer;
use App\Http\Controllers\Controller;
use App\Services\MembershipService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PDF;

class PaymentLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     *
     */
    public function __construct()
    {
        // Avoid repeated "basic_settings limit 1" queries across requests.
        // AppServiceProvider already sets timezone from language-specific settings for most views;
        // this is a safe fallback for places that still rely on Admin BasicSetting.
        $abs = cache()->remember('admin.basic_settings.first', 300, function () {
            return BasicSetting::query()->select('id', 'timezone')->first();
        });
        if ($abs && !empty($abs->timezone)) {
            Config::set('app.timezone', $abs->timezone);
        }
    }

    public function index(Request $request)
    {
        $search = $request->search;
        $data['memberships'] = Membership::query()
            ->select([
                'id',
                'transaction_id',
                'price',
                'status',
                'payment_method',
                'transaction_details',
                'settings',
                'discount',
                'package_price',
                'currency',
                'package_id',
                'user_id',
                'start_date',
                'expire_date',
                'modified',
                'is_trial',
                'created_at',
            ])
            ->with([
                'user:id,first_name,last_name,username,company_name,email,phone',
                'package:id,title,term',
            ])
            ->whereRaw('LOWER(payment_method) = ?', ['arb'])
            ->when($search, function ($query, $search) {
                return $query->where('transaction_id', 'like', '%' . $search . '%');
            })
            ->orderBy('id', 'DESC')
            ->paginate(10);
        return view('admin.payment_log.index', $data);
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

    public function transaction(Request $request)
    {
        $search = $request->search;
        $data['memberships'] = Membership::query()
            ->where('admin_id', Auth::id())
            ->when($search, function ($query, $search) {
                return $query->where('transaction_id', $search);
            })
            ->orderBy('expire_date', 'DESC')
            ->paginate(10);
        return view('admin.transaction.index', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     *
     */
    public function update(Request $request)
    {

        $currentLang = session()->has('lang') ?
            (Language::where('code', session()->get('lang'))->first())
            : (Language::where('is_default', 1)->first());
        $be = $currentLang->basic_extended;
        $bs = $currentLang->basic_setting;
        $membership = Membership::query()->findOrFail($request->id);
        $user = User::query()->findOrFail($membership->user_id);
        $package = Package::query()->findOrFail($membership->package_id);
        $count_membership = Membership::query()->where('user_id', $membership->user_id)->count();
        if ($request->status === "1") {
            $member['first_name'] = $user->first_name;
            $member['last_name'] = $user->last_name;
            $member['username'] = $user->username;
            $member['email'] = $user->email;
            $data['payment_method'] = $membership->payment_method;

            //comparison date
            $date1 = Carbon::createFromFormat('m/d/Y', \Carbon\Carbon::parse($membership->start_date)->format('m/d/Y'));
            $date2 = Carbon::createFromFormat('m/d/Y', \Carbon\Carbon::now()->format('m/d/Y'));
            $result = $date1->gte($date2);
            if($result){
                $data['start_date'] = $membership->start_date;
                $data['expire_date'] = $membership->expire_date;
            }
            else{
                $data['start_date'] = Carbon::today()->format('d-m-Y');
                if ($package->term === "daily") {
                    $data['expire_date'] = Carbon::today()->addDay()->format('d-m-Y');
                } elseif ($package->term === "weekly") {
                    $data['expire_date'] = Carbon::today()->addWeek()->format('d-m-Y');
                } elseif ($package->term === "monthly") {
                    $data['expire_date'] = Carbon::today()->addMonth()->format('d-m-Y');
                } elseif ($package->term === "lifetime") {
                    $data['expire_date'] = Carbon::maxValue()->format('d-m-Y');
                } elseif ($package->term === "trial") {
                    $trialDays = (int) $package->trial_days;
                    if ($trialDays < 1) {
                        $trialDays = MembershipService::DEFAULT_TRIAL_DAYS;
                    }
                    $data['expire_date'] = Carbon::today()->addDays($trialDays)->format('d-m-Y');
                } else {
                    $data['expire_date'] = Carbon::today()->addYear()->format('d-m-Y');
                }
                $membership->update(['start_date' =>  Carbon::parse($data['start_date'])]);
                $membership->update(['expire_date' =>  Carbon::parse($data['expire_date'])]);
            }

            app(MembershipService::class)->expireActiveMemberships($user->id, $membership->id);

            if ($count_membership > 1) {

                $mailTemplate = 'payment_accepted_for_membership_extension_offline_gateway';
                $mailType = 'paymentAcceptedForMembershipExtensionOfflineGateway';
            } else {

                $mailTemplate = 'payment_accepted_for_registration_offline_gateway';
                $mailType = 'paymentAcceptedForRegistrationOfflineGateway';

                $user->update([
                    'status' => 1
                ]);
            }
            $filename = $this->makeInvoice($data, "membership", $member, $user->password, $membership->price, "offline", $user->phone,$be->base_currency_symbol_position,$be->base_currency_symbol,$be->base_currency_text,$membership->transaction_id,$package->title,$membership);

            $mailer = new MegaMailer();
            $data = [
                'toMail' => $user->email,
                'toName' => $user->fname,
                'username' => $user->username,
                'package_title' => $package->title,
                'package_price' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $package->price . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                'discount' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $membership->discount . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                'total' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $membership->price . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                'activation_date' => $data['start_date'],
                'expire_date' => $package->term == "lifetime" ? 'Lifetime' : $data['expire_date'],
                'membership_invoice' => $filename,
                'website_title' => $bs->website_title,
                'templateType' => $mailTemplate,
                'type' => $mailType
            ];
            $mailer->mailFromAdmin($data);
        } elseif ($request->status == 2) {
            if ($count_membership > 1) {

                $mailTemplate = 'payment_rejected_for_membership_extension_offline_gateway';
                $mailType = 'paymentRejectedForMembershipExtensionOfflineGateway';
            } else {

                $mailTemplate = 'payment_rejected_for_registration_offline_gateway';
                $mailType = 'paymentRejectedForRegistrationOfflineGateway';
            }

            $mailer = new MegaMailer();
            $data = [
                'toMail' => $user->email,
                'toName' => $user->fname,
                'username' => $user->username,
                'package_title' => $package->title,
                'package_price' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $package->price . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                'website_title' => $bs->website_title,
                'templateType' => $mailTemplate,
                'type' => $mailType
            ];
            $mailer->mailFromAdmin($data);
        }


        $membership->update(['status' => $request->status]);

        if ((int) $request->status === 1) {
            app(MembershipService::class)->applyPackageTransitionHooks($user, $package->id, 'offline_approval');
        }

        session()->flash('success', "Membership status changed successfully!");
        return back();
    }

    /**
     * Download invoice as PDF for a membership payment.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function downloadInvoice($id)
    {
        try {
            Log::info('Starting PDF generation for membership ID: ' . $id);
            
            $currentLang = session()->has('lang') ?
                (Language::where('code', session()->get('lang'))->first())
                : (Language::where('is_default', 1)->first());
            
            if (!$currentLang) {
                Log::error('Language not found for PDF generation');
                return back()->with('error', 'Language not found');
            }
            
            $be = $currentLang->basic_extended;
            $bs = $currentLang->basic_setting;
            
            if (!$be || !$bs) {
                Log::error('Basic settings not found for PDF generation');
                return back()->with('error', 'Basic settings not found');
            }
            
            Log::info('Loading membership data');
            $membership = Membership::query()->findOrFail($id);
            $user = User::query()->findOrFail($membership->user_id);
            $package = Package::query()->findOrFail($membership->package_id);

            // Prepare member data
            $member = [
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'username' => $user->username ?? '',
                'email' => $user->email ?? '',
            ];

            // Prepare request data for invoice
            $request = [
                'payment_method' => $membership->payment_method ?? 'N/A',
                'start_date' => Carbon::parse($membership->start_date)->format('d-m-Y'),
                'expire_date' => $package->term == "lifetime" 
                    ? Carbon::maxValue()->format('d-m-Y') 
                    : Carbon::parse($membership->expire_date)->format('d-m-Y'),
            ];

            // Prepare all variables for PDF view
            $password = $user->password ?? '';
            $amount = $membership->price ?? 0;
            $payment_method = $membership->payment_method ?? 'N/A';
            $phone = $user->phone_number ?? $user->phone ?? '';
            $base_currency_symbol_position = $be->base_currency_symbol_position ?? 'left';
            $base_currency_symbol = $be->base_currency_symbol ?? '';
            $base_currency_text = $be->base_currency_text ?? '';
            $order_id = $membership->transaction_id ?? 'N/A';
            $package_title = $package->title ?? 'N/A';

            Log::info('Generating PDF view');
            // Generate PDF with inline CSS (no external resources needed)
            $pdf = PDF::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false, // Inline CSS, no remote resources
                'logOutputFile' => storage_path('logs/log.htm'),
                'tempDir' => storage_path('logs/'),
                'defaultFont' => 'sans-serif'
            ])->loadView('pdf.membership', compact(
                'request',
                'member',
                'password',
                'amount',
                'payment_method',
                'phone',
                'base_currency_symbol_position',
                'base_currency_symbol',
                'base_currency_text',
                'order_id',
                'package_title',
                'membership',
                'bs'
            ));

            Log::info('Getting PDF output');
            $filename = 'invoice_' . ($membership->transaction_id ?? $membership->id) . '.pdf';

            // Get PDF output and return as download
            $output = $pdf->output();
            
            Log::info('PDF generated successfully, size: ' . strlen($output) . ' bytes');
            
            return response($output, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($output));
        } catch (\Exception $e) {
            Log::error('PDF Invoice Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'membership_id' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return back()->with('error', 'Failed to generate invoice. Please check the logs for details.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

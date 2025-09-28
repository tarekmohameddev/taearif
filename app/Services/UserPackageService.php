<?php

namespace App\Services;

use Hash;
use Session;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Package;
use App\Models\User\Menu;
use App\Models\Membership;
use App\Models\BasicSetting;
use Illuminate\Http\Request;
use App\Models\BasicExtended;
use App\Models\User\Language;
use App\Models\OfflineGateway;
use App\Models\PaymentGateway;
use App\Http\Helpers\MegaMailer;
use App\Models\User\HomeSection;
use App\Models\User\HomePageText;
use App\Models\User\UserPermission;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Uploader;
use App\Models\User\UserShopSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\User\UserEmailTemplate;
use Illuminate\Support\Facades\Config;
use App\Models\User\UserPaymentGeteway;
use App\Http\Helpers\UserPermissionHelper;
use PhpOffice\PhpSpreadsheet\Calculation\Web;
use App\Models\User\BasicSetting as UserBasicSetting;
use App\Models\User\UserVcard;
use Illuminate\Support\Facades\DB;
use App\Models\UserStep;
use Illuminate\Support\Facades\Log;

class UserPackageService
{
    public function changeCurrentPackage(Request $request)
    {
        Log::info('here UserPackageService');
        $userId = $request->user_id;
        log::info('Changing current package for user ID: ' . $userId);
        $user = User::findOrFail($userId);
        $currMembership = UserPermissionHelper::currMembOrPending($userId);
        $nextMembership = UserPermissionHelper::nextMembership($userId);
        log::info('Current membership: ' . ($currMembership ? $currMembership->id : 'None'));
        $be = BasicExtended::first();
        $bs = BasicSetting::select('website_title')->first();
        Log::info('Basic settings and extended settings retrieved successfully.');
        $selectedPackage = Package::find($request->package_id);

        if (!empty($nextMembership) && $selectedPackage->term == 'lifetime') {
            Session::flash('membership_warning', 'To add a Lifetime package as Current Package, You have to remove the next package');
            return back();
        }
        log::info('Selected package: ' . $selectedPackage->title);
        $currMembership->expire_date = Carbon::now()->subDay()->format('Y-m-d');
        $currMembership->modified = 1;
        if ($currMembership->status == 0) {
            $currMembership->status = 2;
        }
        $currMembership->save();

        if ($selectedPackage->term == 'monthly') {
            $exDate = Carbon::now()->addMonth()->format('Y-m-d');
        } elseif ($selectedPackage->term == 'yearly') {
            $exDate = Carbon::now()->addYear()->format('Y-m-d');
        } else {
            $exDate = Carbon::maxValue()->format('Y-m-d');
        }

        $selectedMemb = Membership::create([
            'price' => $selectedPackage->price,
            'currency' => $be->base_currency_text,
            'currency_symbol' => $be->base_currency_symbol,
            'payment_method' => $request->payment_method,
            'transaction_id' => uniqid(),
            'status' => 1,
            'receipt' => null,
            'transaction_details' => null,
            'settings' => json_encode($be),
            'package_id' => $selectedPackage->id,
            'user_id' => $userId,
            'start_date' => Carbon::now()->format('Y-m-d'),
            'expire_date' => $exDate,
            'is_trial' => 0,
            'trial_days' => 0,
        ]);

        if (!empty($nextMembership) && $selectedPackage->term != 'lifetime') {
            $nextPackage = Package::find($nextMembership->package_id);
            $nextStart = Carbon::parse($exDate)->addDay();

            $nextMembership->start_date = $nextStart->format('Y-m-d');

            if ($nextPackage->term == 'monthly') {
                $nextExpire = $nextStart->copy()->addMonth();
            } elseif ($nextPackage->term == 'yearly') {
                $nextExpire = $nextStart->copy()->addYear();
            } else {
                $nextExpire = Carbon::maxValue();
            }

            $nextMembership->expire_date = $nextExpire->format('Y-m-d');
            $nextMembership->save();
        }

        $currentPackage = Package::select('title')->findOrFail($currMembership->package_id);
        // $this->sendMail(...) // leave this for now
        Session::flash('success', 'Current Package changed successfully!');
        return back();
    }

    public function addCurrentPackage(Request $request)
    {
        $userId = $request->user_id;
        $user = User::findOrFail($userId);
        $be = BasicExtended::first();
        $bs = BasicSetting::select('website_title')->first();

        $selectedPackage = Package::find($request->package_id);

        // Special handling for free package (ID: 16) when switching from trial (ID: 26)
        if ($selectedPackage->id == 16 && $request->payment_method == 'system') {
            // Check if user had a trial package that just expired
            $expiredTrialMembership = Membership::where('user_id', $userId)
                ->where('package_id', 26) // Trial package ID
                ->where('status', 1)
                ->whereDate('expire_date', '<', now()->toDateString())
                ->orderByDesc('expire_date')
                ->first();
            
            if ($expiredTrialMembership) {
                // Set 1-year expiry for free package when coming from trial
                $exDate = Carbon::now()->addYear()->format('d-m-Y');
                Log::info('Trial to Free Package Switch: User ID ' . $userId . ' switched from trial package (ID: 26) to free package (ID: 16) with 1-year expiry');
            } else {
                // Use normal package term logic for other cases
                if ($selectedPackage->term == 'monthly') {
                    $exDate = Carbon::now()->addMonth()->format('d-m-Y');
                } elseif ($selectedPackage->term == 'yearly') {
                    $exDate = Carbon::now()->addYear()->format('d-m-Y');
                } else {
                    $exDate = Carbon::maxValue()->format('d-m-Y');
                }
            }
        } else {
            // Normal package term logic for all other cases
            if ($selectedPackage->term == 'monthly') {
                $exDate = Carbon::now()->addMonth()->format('d-m-Y');
            } elseif ($selectedPackage->term == 'yearly') {
                $exDate = Carbon::now()->addYear()->format('d-m-Y');
            } else {
                $exDate = Carbon::maxValue()->format('d-m-Y');
            }
        }

        $selectedMemb = Membership::create([
            'price' => $selectedPackage->price,
            'currency' => $be->base_currency_text,
            'currency_symbol' => $be->base_currency_symbol,
            'payment_method' => $request->payment_method,
            'transaction_id' => uniqid(),
            'status' => 1,
            'receipt' => null,
            'transaction_details' => null,
            'settings' => json_encode($be),
            'package_id' => $selectedPackage->id,
            'user_id' => $userId,
            'start_date' => Carbon::parse(Carbon::now()->format('d-m-Y')),
            'expire_date' => Carbon::parse($exDate),
            'is_trial' => 0,
            'trial_days' => 0,
        ]);



        Session::flash('success', 'Current Package has been added successfully!');
        return back();
    }

}

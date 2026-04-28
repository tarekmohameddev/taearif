<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Package;
use Illuminate\Http\Request;
use App\Models\User\BasicSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;

class SubdomainController extends Controller
{

    public function __construct()
    {
        $abs = BasicSetting::first();
        Config::set('app.timezone', $abs->timezone);
    }

    public function index(Request $request)
    {
        $type = $request->type;
        $username = $request->username;

        // Filter users at the DB level instead of looping all users and calling helpers (prevents N+1)
        $subdomainPackageIds = Package::query()
            ->where('features', 'LIKE', '%"Subdomain"%')
            ->pluck('id');

        $subdomains = User::query()
            ->whereHas('memberships', function ($q) use ($subdomainPackageIds) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'))
                    ->when($subdomainPackageIds->isNotEmpty(), function ($mq) use ($subdomainPackageIds) {
                        $mq->whereIn('package_id', $subdomainPackageIds);
                    }, function ($mq) {
                        // If no packages have the Subdomain feature, return no results.
                        $mq->whereRaw('1 = 0');
                    });
            })
            ->when($type, function ($query, $type) {
            if ($type == 'pending') {
                return $query->where('subdomain_status', 0);
            } elseif ($type == 'connected') {
                return $query->where('subdomain_status', 1);
            }
        })->when($username, function ($query, $username) {
            return $query->where('username', 'LIKE', '%' . $username . '%');
        })
            ->latest()
            ->paginate(10);
        $data['subdomains'] = $subdomains;

        return view('admin.subdomains.index', $data);
    }

    public function status(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->subdomain_status = $request->status;
        $user->save();

        $request->session()->flash('success', 'Status updated successfully');
        return back();
    }
}

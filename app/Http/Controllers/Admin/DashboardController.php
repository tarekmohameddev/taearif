<?php

namespace App\Http\Controllers\Admin;

use App\Models\BasicSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Services\Admin\AdminDashboardMetricsService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $timezone = BasicSetting::query()->value('timezone');

        if (!empty($timezone)) {
            Config::set('app.timezone', $timezone);
        }
    }

    public function dashboard(AdminDashboardMetricsService $metrics)
    {
        $admin = Auth::guard('admin')->user();

        return view('admin.dashboard', [
            'dashboard' => $metrics->build($admin),
        ]);
    }

    public function changeTheme(Request $request)
    {
        return redirect()->back()->withCookie(cookie()->forever('admin-theme', $request->theme));
    }
}

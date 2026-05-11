<?php

namespace App\Http\Controllers\Admin;

use DB;
use Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Language;
use App\Models\Membership;
use App\Models\ApiCustomer;
use App\Models\BasicSetting;
use App\Models\Subscriber;
use App\Models\Package;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\Blog;

class DashboardController extends Controller
{
    public function __construct()
    {
        $abs = BasicSetting::first();
        Config::set('app.timezone', $abs->timezone);
    }

    public function dashboard()
    {
        $data['incomes'] = Membership::select(
            DB::raw('MONTH(created_at) month'),
            DB::raw('sum(price) total')
            )->where('status', 1)
            ->groupBy('month')
            ->whereYear('created_at', date('Y'))
            ->get();

        $data['users'] = User::join('memberships', 'users.id', '=', 'memberships.user_id')
            ->select(DB::raw('MONTH(users.created_at) month'), DB::raw('count(*) total'))
            ->groupBy('month')
            ->whereYear('users.created_at', date('Y'))
            ->where([
                ['memberships.status', '=', 1],
                ['memberships.start_date', '<=', Carbon::now()->format('Y-m-d')],
                ['memberships.expire_date', '>=', Carbon::now()->format('Y-m-d')],
                ['users.account_type', '=', 'tenant']
            ])
            ->get();

        $data['defaultLang'] = app('defaultLanguage');

        $data['counts'] = [
            'users' => (int) User::count(),
            'subscribers' => (int) Subscriber::count(),
            'packages' => (int) Package::count(),
            'memberships' => (int) Membership::count(),
            'admins' => (int) Admin::count(),
            'blogs' => (int) Blog::where('language_id', optional($data['defaultLang'])->id)->count(),
        ];

        $data['customersTotal'] = ApiCustomer::count();
        $data['projectsTotal']  = Project::count();
        $data['propertiesPurposeTotals'] = Property::select(
            'purpose',
            DB::raw('COUNT(*) AS total')
        )
        ->groupBy('purpose')
        ->orderByDesc('total')
        ->get();

    $data['propertiesTotalAll'] = Property::count();

        return view('admin.dashboard', $data);
    }

    public function changeTheme(Request $request)
    {
        return redirect()->back()->withCookie(cookie()->forever('admin-theme', $request->theme));
    }
}

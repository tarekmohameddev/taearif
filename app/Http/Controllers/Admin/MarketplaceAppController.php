<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Marketplace\Services\MarketplaceAppService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarketplaceApp\StoreMarketplaceAppRequest;
use App\Http\Requests\Admin\MarketplaceApp\UpdateMarketplaceAppRequest;
use App\Enums\BillingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;

class MarketplaceAppController extends Controller
{
    public function __construct(
        private MarketplaceAppService $appService
    ) {
    }

    /**
     * Display a listing of marketplace apps
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->search,
        ];

        $data['apps'] = $this->appService->getApps($filters, 20);
        $data['billingTypes'] = BillingType::cases();
        
        return view('admin.marketplace-apps.index', $data);
    }

    /**
     * Show the form for creating a new app
     */
    public function create()
    {
        $data['billingTypes'] = BillingType::cases();
        return view('admin.marketplace-apps.create', $data);
    }

    /**
     * Store a newly created app
     */
    public function store(StoreMarketplaceAppRequest $request)
    {
        $data = $request->validated();
        $image = $request->hasFile('image') ? $request->file('image') : null;

        $this->appService->createApp($data, $image);

        // Handle AJAX requests (maintain backward compatibility)
        if ($request->expectsJson() || $request->ajax()) {
            return "success";
        }

        Session::flash('success', 'تم إنشاء تطبيق المتجر بنجاح!');
        return redirect()->route('admin.marketplace-apps.index');
    }

    /**
     * Show the form for editing an app
     */
    public function edit($id)
    {
        $data['app'] = $this->appService->getAppById($id);
        $data['billingTypes'] = BillingType::cases();
        return view('admin.marketplace-apps.edit', $data);
    }

    /**
     * Update an app
     */
    public function update(UpdateMarketplaceAppRequest $request)
    {
        $data = $request->validated();
        $image = $request->hasFile('image') ? $request->file('image') : null;
        $appId = $data['app_id'];
        unset($data['app_id']); // Remove app_id from data array

        $this->appService->updateApp($appId, $data, $image);

        // Clear sidebar cache since app changes affect sidebar
        $this->clearSidebarCache();

        // Handle AJAX requests (maintain backward compatibility)
        if ($request->expectsJson() || $request->ajax()) {
            return "success";
        }

        Session::flash('success', 'تم تحديث تطبيق المتجر بنجاح!');
        return redirect()->route('admin.marketplace-apps.index');
    }

    /**
     * Delete an app
     */
    public function delete(Request $request)
    {
        $request->validate([
            'app_id' => 'required|exists:api_apps,id',
        ]);

        $this->appService->deleteApp($request->app_id);

        // Clear sidebar cache since deleted app should not appear in sidebar
        $this->clearSidebarCache();

        Session::flash('success', 'تم حذف تطبيق المتجر بنجاح!');
        return back();
    }

    /**
     * Bulk delete apps
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:api_apps,id',
        ]);

        $deletedCount = $this->appService->bulkDeleteApps($request->ids);

        // Clear sidebar cache since deleted apps should not appear in sidebar
        $this->clearSidebarCache();

        // Handle AJAX requests (maintain backward compatibility)
        if ($request->expectsJson() || $request->ajax()) {
            return "success";
        }

        Session::flash('success', "تم حذف {$deletedCount} تطبيق(ات) المتجر بنجاح!");
        return redirect()->route('admin.marketplace-apps.index');
    }

    /**
     * Toggle app enabled/disabled status
     */
    public function toggleStatus(Request $request)
    {
        $request->validate([
            'app_id' => 'required|exists:api_apps,id',
        ]);

        $app = $this->appService->toggleAppStatus($request->app_id);

        // Clear sidebar cache since app visibility affects sidebar
        $this->clearSidebarCache();

        // Handle AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $app->is_enabled ? 'تم تفعيل التطبيق بنجاح' : 'تم إلغاء تفعيل التطبيق بنجاح',
                'is_enabled' => $app->is_enabled,
            ]);
        }

        Session::flash('success', $app->is_enabled ? 'تم تفعيل التطبيق بنجاح!' : 'تم إلغاء تفعيل التطبيق بنجاح!');
        return back();
    }

    /**
     * Clear all sidebar cache entries
     */
    private function clearSidebarCache()
    {
        // Clear cache by pattern (Laravel doesn't support pattern deletion natively)
        // We'll need to clear the entire cache or use a tag-based cache if available
        Cache::flush();
    }
}


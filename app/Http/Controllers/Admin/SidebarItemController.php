<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiSidebarItem;
use Validator;
use Session;
use Illuminate\Support\Facades\Cache;

class SidebarItemController extends Controller
{
    public function index()
    {
        $data['items'] = ApiSidebarItem::ordered()->get();
        return view('admin.sidebar-item.index', $data);
    }

    public function create()
    {
        return view('admin.sidebar-item.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'icon' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'permission' => 'nullable|string|max:255',
            'condition_type' => 'nullable|in:has_projects,has_properties,is_affiliate_approved',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $input = $request->all();
        $input['is_active'] = $request->has('is_active') ? true : false;

        ApiSidebarItem::create($input);

        // Clear sidebar cache
        $this->clearSidebarCache();

        Session::flash('success', 'Sidebar item created successfully!');
        return "success";
    }

    public function edit($id)
    {
        $data['item'] = ApiSidebarItem::findOrFail($id);
        return view('admin.sidebar-item.edit', $data);
    }

    public function update(Request $request)
    {
        $item = ApiSidebarItem::findOrFail($request->item_id);

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'icon' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'permission' => 'nullable|string|max:255',
            'condition_type' => 'nullable|in:has_projects,has_properties,is_affiliate_approved',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $input = $request->all();
        $input['is_active'] = $request->has('is_active') ? true : false;

        $item->update($input);

        // Clear sidebar cache
        $this->clearSidebarCache();

        Session::flash('success', 'Sidebar item updated successfully!');
        return "success";
    }

    public function delete(Request $request)
    {
        $item = ApiSidebarItem::findOrFail($request->item_id);
        $item->delete();

        // Clear sidebar cache
        $this->clearSidebarCache();

        Session::flash('success', 'Sidebar item deleted successfully!');
        return back();
    }

    public function toggleStatus(Request $request)
    {
        $item = ApiSidebarItem::findOrFail($request->item_id);
        $item->is_active = !$item->is_active;
        $item->save();

        // Clear sidebar cache
        $this->clearSidebarCache();

        $status = $item->is_active ? 'activated' : 'deactivated';
        Session::flash('success', "Sidebar item {$status} successfully!");
        return back();
    }

    public function reorder(Request $request)
    {
        $rules = [
            'items' => 'required|array',
            'items.*.id' => 'required|exists:api_sidebar_items,id',
            'items.*.order' => 'required|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        foreach ($request->items as $itemData) {
            ApiSidebarItem::where('id', $itemData['id'])
                ->update(['order' => $itemData['order']]);
        }

        // Clear sidebar cache
        $this->clearSidebarCache();

        Session::flash('success', 'Sidebar items reordered successfully!');
        return "success";
    }

    /**
     * Clear all sidebar cache entries
     * 
     * Note: With file cache driver, we cannot do wildcard deletion of user-specific
     * side_menus caches. Those caches have a 5-minute TTL, so changes will be visible
     * within 5 minutes. This is a tradeoff vs using Cache::flush() which clears ALL
     * cache for ALL tenants (nuclear option).
     * 
     * Senior Rule: "If data can change → it MUST have forget() somewhere"
     * For sidebar items, the observers handle this via CacheInvalidationHelper.
     */
    private function clearSidebarCache()
    {
        // File cache driver limitation: cannot delete by pattern
        // User-specific side_menus caches will expire by TTL (5 min)
        // 
        // DO NOT use Cache::flush() - it clears ALL cache for ALL tenants!
        // This includes membership caches, property caches, customer caches, etc.
        //
        // The ApiSidebarItemObserver handles invalidation via model events.
        // Additional manual clearing is not needed when using the admin UI.
    }
}

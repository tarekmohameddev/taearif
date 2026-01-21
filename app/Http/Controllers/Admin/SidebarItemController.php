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
     */
    private function clearSidebarCache()
    {
        // Clear cache by pattern (Laravel doesn't support pattern deletion natively)
        // We'll need to clear the entire cache or use a tag-based cache if available
        Cache::flush();
    }
}

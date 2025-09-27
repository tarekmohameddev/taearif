<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TenantPage;
use App\Models\TenantGlobalComponent;

class GetTenantController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'websiteName' => 'required|string',
        ]);
        $tenant = User::where('username', $data['websiteName'])->first();
        if (!$tenant) {
            return response()->json([], 204);
        }
        $pages = TenantPage::where('user_id', $tenant->id)->get()->keyBy('page_id')->map->components;
        $globals = TenantGlobalComponent::where('user_id', $tenant->id)->first();
        return response()->json([
            'username' => $tenant->username,
            'websiteName' => $tenant->username,
            'componentSettings' => $pages,
            'globalComponentsData' => $globals?->data ?? [],
        ]);
    }
}



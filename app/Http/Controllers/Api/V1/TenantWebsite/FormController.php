<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantWebsite\ContactFormRequest;
use App\Models\TenantFormSubmission;
use App\Models\User;

class FormController extends Controller
{
    public function store(ContactFormRequest $request, string $tenantId)
    {
        $tenant = User::where('username', $tenantId)->firstOrFail();
        $sub = TenantFormSubmission::create([
            'user_id' => $tenant->id,
            'form_type' => 'contact',
            'data' => $request->validated(),
            'submitted_at' => now(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        return response()->json(['success' => true, 'id' => $sub->id]);
    }
}



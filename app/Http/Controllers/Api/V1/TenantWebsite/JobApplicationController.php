<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Models\User\JobApplication;
use App\Http\Requests\TenantWebsite\JobApplication\StoreRequest;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use Illuminate\Support\Str;

class JobApplicationController extends Controller
{
    use ResolvesTenant;

    public function store(StoreRequest $request, string $tenantId)
    {
        $tenant = $this->resolveTenant($request, $tenantId);
        $validated = $request->validated();

        $pdfPath = $request->file('pdf')->storeAs('job_applications', Str::uuid() . '.pdf', 'public');

        $app = JobApplication::create([
            'user_id' => $tenant->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'description' => $validated['description'] ?? null,
            'pdf_path' => $pdfPath,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $app->id,
            ],
        ], 201);
    }
}

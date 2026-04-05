<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Models\User\JobApplication;
use App\Http\Requests\TenantWebsite\JobApplication\StoreRequest;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use App\Services\AlibabaOssService;
use Illuminate\Support\Facades\Log;

class JobApplicationController extends Controller
{
    use ResolvesTenant;

    private AlibabaOssService $ossService;

    public function __construct(AlibabaOssService $ossService)
    {
        $this->ossService = $ossService;
    }

    public function store(StoreRequest $request, string $tenantId)
    {
        $tenant = $this->resolveTenant($request, $tenantId);
        $validated = $request->validated();

        try {
            $result = $this->ossService->uploadFile(
                $request->file('pdf'),
                'job_applications/' . $tenant->id
            );
            $pdfPath = $result['url'];
        } catch (\Exception $e) {
            Log::warning('Job application PDF upload failed', [
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenantId,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            $payload = [
                'success' => false,
                'message' => 'Failed to upload PDF.',
            ];
            if (config('app.debug')) {
                $payload['debug'] = $e->getMessage();
            }
            return response()->json($payload, 500);
        }

        $app = JobApplication::create([
            'user_id' => $tenant->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'description' => $validated['description'] ?? null,
            'pdf_path' => $pdfPath,
        ]);

        Log::info('Job application submitted', [
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenantId,
            'job_application_id' => $app->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $app->id,
            ],
        ], 201);
    }
}

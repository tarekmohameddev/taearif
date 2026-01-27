<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Api\ApiPixel;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;

class PixelController extends Controller
{
    use ResolvesTenant;

    /**
     * Map platform values to provider names
     *
     * @param string $platform
     * @return string
     */
    private function mapPlatformToProvider(string $platform): string
    {
        return match ($platform) {
            'facebook' => 'meta',
            'tiktok' => 'tiktok',
            'snapchat' => 'snapchat',
            default => $platform, // Return original if unknown
        };
    }

    /**
     * Get pixels for a specific tenant
     *
     * @param Request $request
     * @param string $tenantId
     * @return JsonResponse
     */
    public function index(Request $request, string $tenantId): JsonResponse
    {
        // Resolve tenant (throws ModelNotFoundException if not found)
        $tenant = $this->resolveTenant($request, $tenantId);

        // Query active pixels for this tenant
        $pixels = ApiPixel::where('user_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('platform')
            ->get();

        // Map pixels to required response format
        $data = $pixels->map(function ($pixel) {
            return [
                'tenantid' => '', // Empty string as per requirements
                'provider' => $this->mapPlatformToProvider($pixel->platform),
                'externalId' => $pixel->pixel_id,
                'settings' => [
                    'loadOnStorefront' => (bool) $pixel->is_active,
                ],
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}

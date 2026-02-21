<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Pixel\StorePixelRequest;
use App\Http\Requests\Api\Pixel\TogglePixelStatusRequest;
use App\Http\Requests\Api\Pixel\UpdatePixelRequest;
use App\Models\Api\ApiPixel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class PixelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $pixels = ApiPixel::where('user_id', $user->id)
            ->orderBy('platform')
            ->get()
            ->map(function ($pixel) {
                return [
                    'id' => $pixel->id,
                    'platform' => $pixel->platform,
                    'pixel_id' => $pixel->pixel_id,
                    'is_active' => $pixel->is_active,
                    'created_at' => $pixel->created_at,
                    'updated_at' => $pixel->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pixels,
            'message' => 'Pixels retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(StorePixelRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();

        // Check if pixel already exists for this platform
        $existingPixel = ApiPixel::where('user_id', $user->id)
            ->where('platform', $validated['platform'])
            ->first();

        if ($existingPixel) {
            return response()->json([
                'success' => false,
                'message' => 'Pixel already exists for this platform'
            ], 409);
        }

        $pixel = ApiPixel::create([
            'user_id' => $user->id,
            'platform' => $validated['platform'],
            'pixel_id' => $validated['pixel_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pixel->id,
                'platform' => $pixel->platform,
                'pixel_id' => $pixel->pixel_id,
                'is_active' => $pixel->is_active,
                'created_at' => $pixel->created_at,
                'updated_at' => $pixel->updated_at,
            ],
            'message' => 'Pixel created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        $pixel = ApiPixel::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$pixel) {
            return response()->json([
                'success' => false,
                'message' => 'Pixel not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pixel->id,
                'platform' => $pixel->platform,
                'pixel_id' => $pixel->pixel_id,
                'is_active' => $pixel->is_active,
                'created_at' => $pixel->created_at,
                'updated_at' => $pixel->updated_at,
            ],
            'message' => 'Pixel retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdatePixelRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $pixel = ApiPixel::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$pixel) {
            return response()->json([
                'success' => false,
                'message' => 'Pixel not found'
            ], 404);
        }

        // Check if platform is being changed and if it conflicts with existing pixel
        if (array_key_exists('platform', $validated) && $validated['platform'] !== $pixel->platform) {
            $existingPixel = ApiPixel::where('user_id', $user->id)
                ->where('platform', $validated['platform'])
                ->where('id', '!=', $id)
                ->first();

            if ($existingPixel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pixel already exists for this platform'
                ], 409);
            }
        }

        $pixel->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pixel->id,
                'platform' => $pixel->platform,
                'pixel_id' => $pixel->pixel_id,
                'is_active' => $pixel->is_active,
                'created_at' => $pixel->created_at,
                'updated_at' => $pixel->updated_at,
            ],
            'message' => 'Pixel updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        $pixel = ApiPixel::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$pixel) {
            return response()->json([
                'success' => false,
                'message' => 'Pixel not found'
            ], 404);
        }

        $pixel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pixel deleted successfully'
        ]);
    }

    /**
     * Toggle the active status of a pixel.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(TogglePixelStatusRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        $pixel = ApiPixel::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$pixel) {
            return response()->json([
                'success' => false,
                'message' => 'Pixel not found'
            ], 404);
        }

        $pixel->update(['is_active' => !$pixel->is_active]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pixel->id,
                'platform' => $pixel->platform,
                'pixel_id' => $pixel->pixel_id,
                'is_active' => $pixel->is_active,
                'created_at' => $pixel->created_at,
                'updated_at' => $pixel->updated_at,
            ],
            'message' => 'Pixel status toggled successfully'
        ]);
    }
} 
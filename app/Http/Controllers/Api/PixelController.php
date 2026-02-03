<?php

namespace App\Http\Controllers\Api;

use App\Models\Api\ApiPixel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

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
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:facebook,tiktok,snapchat,gtm',
            'pixel_id' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check if pixel already exists for this platform
        $existingPixel = ApiPixel::where('user_id', $user->id)
            ->where('platform', $request->platform)
            ->first();

        if ($existingPixel) {
            return response()->json([
                'success' => false,
                'message' => 'Pixel already exists for this platform'
            ], 409);
        }

        $pixel = ApiPixel::create([
            'user_id' => $user->id,
            'platform' => $request->platform,
            'pixel_id' => $request->pixel_id,
            'is_active' => $request->get('is_active', true),
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
    public function update(Request $request, int $id): JsonResponse
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

        $validator = Validator::make($request->all(), [
            'platform' => 'sometimes|in:facebook,tiktok,snapchat,gtm',
            'pixel_id' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if platform is being changed and if it conflicts with existing pixel
        if ($request->has('platform') && $request->platform !== $pixel->platform) {
            $existingPixel = ApiPixel::where('user_id', $user->id)
                ->where('platform', $request->platform)
                ->where('id', '!=', $id)
                ->first();

            if ($existingPixel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pixel already exists for this platform'
                ], 409);
            }
        }

        $pixel->update($request->only(['platform', 'pixel_id', 'is_active']));

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
    public function toggleStatus(Request $request, int $id): JsonResponse
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
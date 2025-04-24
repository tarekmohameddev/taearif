<?php

namespace App\Http\Controllers\Api\content;

use App\Models\Api\ApiAboutSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class AboutApiController extends Controller
{
    public function index(Request $request)
    {
        // Get the about data
        $user = $request->user();
        $about = ApiAboutSettings::where('user_id', $user->id)->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'about' => $about
            ]
        ]);
    }

    /**
     * Update about page content
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'history' => 'nullable|string',
            'mission' => 'nullable|string',
            'vision' => 'nullable|string',
            'image_path' => 'nullable|string',
            'features' => 'required|array',
            'features.*.id' => 'required|integer',
            'features.*.title' => 'required|string|max:255',
            'features.*.description' => 'required|string',
            'status' => 'required|string|in:on,off',
        ]);



        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            // Get or create the about record
            $about = ApiAboutSettings::where('user_id', $user->id)->first();
            if (!$about) {
                $about = new ApiAboutSettings();
                $about->user_id = $user->id;
            }

            // Update about data
            $about->title = $request->title;
            $about->subtitle = $request->subtitle;
            $about->history = $request->history;
            $about->mission = $request->mission;
            $about->vision = $request->vision;
            $about->features = $request->features;
            $about->image_path = $request->image_path;
            $about->status = $request->status;

            $about->save();

            $responseAbout = $about->toArray();
            $responseAbout['image_path'] = asset($about->image_path);

            return response()->json([
                'status' => 'success',
                'message' => 'About page updated successfully',
                'data' => [
                    'about' => $responseAbout
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update about page',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

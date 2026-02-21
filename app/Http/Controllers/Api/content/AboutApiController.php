<?php

namespace App\Http\Controllers\Api\content;

use App\Models\Api\ApiAboutSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Content\UpdateAboutSettingsRequest;
use Illuminate\Http\Request;


class AboutApiController extends Controller
{
    public function index(Request $request)
    {
        // Get the about data
        $user = auth()->user();
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
    public function update(UpdateAboutSettingsRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        try {

            // Get or create the about record
            $about = ApiAboutSettings::where('user_id', $user->id)->first();
            if (!$about) {
                $about = new ApiAboutSettings();
                $about->user_id = $user->id;
            }

            // Update about data
            $about->title = $validated['title'];
            $about->subtitle = $validated['subtitle'] ?? null;
            $about->history = $validated['history'] ?? null;
            $about->mission = $validated['mission'] ?? null;
            $about->vision = $validated['vision'] ?? null;
            $about->features = $validated['features'];
            $about->image_path = $validated['image_path'] ?? null;
            $about->status = $validated['status'];

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

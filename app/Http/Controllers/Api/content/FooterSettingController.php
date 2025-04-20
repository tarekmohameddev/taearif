<?php

namespace App\Http\Controllers\Api\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Api\FooterSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class FooterSettingController extends Controller
{
    /**
     * Get the footer settings for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {


        $user = $request->user();
        $settings = FooterSetting::where('user_id', $user->id)->first();

        return response()->json([
            'status' => 'success',
            'data' => ['settings' => $settings]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the footer settings
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Validation
        $validator = Validator::make($request->all(), [
            'general' => 'required|array',
            'general.companyName' => 'required|string|max:100',
            'general.address' => 'nullable|string|max:255',
            'general.phone' => 'nullable|string|max:20',
            'general.email' => 'nullable|email|max:100',
            'general.workingHours' => 'nullable|string|max:100',
            'general.showContactInfo' => 'boolean',
            'general.showWorkingHours' => 'boolean',
            'general.copyrightText' => 'nullable|string|max:255',
            'general.showCopyright' => 'boolean',

            'social' => 'required|array',
            'social.*.id' => 'required|string',
            'social.*.platform' => 'required|string|in:facebook,twitter,instagram,linkedin,youtube',
            'social.*.url' => 'nullable|string|max:255',
            'social.*.enabled' => 'boolean',

            'columns' => 'required|array',
            'columns.*.id' => 'required|string',
            'columns.*.title' => 'required|string|max:100',
            'columns.*.enabled' => 'boolean',
            'columns.*.links' => 'required|array',
            'columns.*.links.*.id' => 'required|string',
            'columns.*.links.*.text' => 'required|string|max:100',
            'columns.*.links.*.url' => 'required|string|max:255',

            'newsletter' => 'required|array',
            'newsletter.enabled' => 'boolean',
            'newsletter.title' => 'required|string|max:100',
            'newsletter.description' => 'nullable|string|max:255',
            'newsletter.buttonText' => 'required|string|max:50',
            'newsletter.placeholderText' => 'required|string|max:100',

            'style' => 'required|array',
            'style.layout' => 'required|string|in:full-width,contained',
            'style.backgroundColor' => 'required|string|max:20',
            'style.textColor' => 'required|string|max:20',
            'style.accentColor' => 'required|string|max:20',
            'style.columns' => 'required|integer|min:1|max:4',
            'style.showSocialIcons' => 'boolean',
            'style.socialIconsPosition' => 'required|string|in:top,bottom',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find or create settings
        $settings = FooterSetting::updateOrCreate(
            ['user_id' => $user->id],
            $request->only(['general', 'social', 'columns', 'newsletter', 'style'])
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Footer settings updated successfully',
            'data' => ['settings' => $settings]
        ]);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

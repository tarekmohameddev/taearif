<?php

namespace App\Http\Controllers\Api\content;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiContentSection;
use App\Models\Api\GeneralSetting;
use App\Models\Api\ApiBannerSetting;
use App\Models\Api\ApiAboutSettings;
use App\Models\Api\FooterSetting;
use App\Models\Api\ApiDomainSetting;
use Carbon\Carbon;

class ApiContentSectionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Carbon::setLocale('ar');
        $userId = auth()->id();

        $hiddenSections = [];

        if (ApiBannerSetting::where('user_id', $userId)->exists()) {
            $hiddenSections[] = 'banner';
        }

        if (ApiAboutSettings::where('user_id', $userId)->exists()) {
            $hiddenSections[] = 'about';
        }

        $sections = ApiContentSection::all()
        ->filter(function ($section) use ($hiddenSections) {
            return !in_array($section->section_id, $hiddenSections);
        })
        ->map(function ($section) {
            $status = $this->getStatusFromRelatedTable($section->section_id);

            return [
                'id' => $section->section_id,
                'title' => $section->title,
                'description' => $section->description,
                'icon' => $section->icon,
                'path' => $section->path,
                'status' => $status,
                'count' => $section->count,
                'info' => [
                    'email' => $section->description,
                    'website' => optional(json_decode($section->info))->website ?? null,
                ],
                'badge' => json_decode($section->badge),
                'lastUpdate' => $section->updated_at->toIso8601String(),
                'lastUpdateFormatted' => $section->lastUpdateFormatted,
            ];
        })
        ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'sections' => $sections,
            ],
        ]);
    }

    private function getStatusFromRelatedTable($sectionId)
    {
        $userId = auth()->id();

        $models = [
            'general' => GeneralSetting::class,
            'about'   => ApiAboutSettings::class,
            'banner'  => ApiBannerSetting::class,
            'footer'  => FooterSetting::class,
            'domains' => ApiDomainSetting::class,
        ];

        if (!isset($models[$sectionId])) {
            return false;
        }

        $modelClass = $models[$sectionId];

        $record = $modelClass::where('user_id', $userId)->first();

        return $record ? (bool) $record->status : false;
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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

<?php

namespace App\Http\Controllers\Api;

use App\Models\UserStep;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StepProgressController extends Controller
{
    /**
     * Step map configuration - moved to class constant to avoid per-request allocation
     */
    private const STEP_MAP = [
        'footer' => [
            'path' => '/content/footer',
            'text' => "قم بتخصيص التذييل الخاص بك",
        ],
        'properties' => [
            'path' => '/properties/add',
            'text' => "اضف اول عقار الآن",
        ],
    ];

    /**
     * Display the progress of a specific step for a user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSteps(Request $request)
    {
        $user = $request->user();
        
        // Check if performance optimizations are enabled
        $useOptimizations = config('performance.enable_api_performance_optimizations');
        
        if ($useOptimizations) {
            // Select only the columns we use to avoid pulling large blobs
            $stepKeys = array_keys(self::STEP_MAP);
            $steps = UserStep::select(['user_id', ...$stepKeys])
                ->firstOrCreate(['user_id' => $user->id], array_fill_keys($stepKeys, false));
        } else {
            $steps = UserStep::firstOrCreate(['user_id' => $user->id]);
        }

        $stepMap = self::STEP_MAP;
        $rawData = $steps->only(array_keys($stepMap));

    $stepsWithStatus = [];
    foreach ($stepMap as $key => $info) {
        $value = $rawData[$key] ?? null;
        $stepsWithStatus[$key] = [
            'status' => $value,
            'text' => $info['text'],
        ];
    }

    $progress = collect($stepsWithStatus)->filter(fn($step) => $step['status'])->count();
    $percentage = intval(($progress / count($stepMap)) * 100);

    $continuePath = collect($stepMap)
        ->filter(fn($_, $key) => empty($rawData[$key]))
        ->pluck('path')
        ->first();

    return response()->json([
        'steps' => $stepsWithStatus,
        'progress' => $percentage,
        'continue_path' => $continuePath,
    ]);
}


    public function completeStep(Request $request)
    {

        $request->validate([
            'step' => 'required|in:banner,footer,homepage_about_update,menu_builder,projects,properties',
        ]);

        $user = $request->user();
        $steps = UserStep::firstOrCreate(['user_id' => $user->id]);

        $steps->{$request->step} = true;
        // Optional: check if all steps are completed now
        $stepKeys = ['banner','footer','about','menu','projects','properties'];
        $remaining = collect($steps->only($stepKeys))->filter(fn($v) => !$v);

        if ($remaining->isEmpty() && !$steps->completed_at) {
            $steps->completed_at = now();
        }

        $steps->save();

        $data = $steps->only($stepKeys);
        $progress = collect($data)->filter(fn($v) => $v)->count();
        $percentage = intval(($progress / count($stepKeys)) * 100);

        $continuePath = collect(array_combine($stepKeys, [
            '/content/banner',
            '/content/footer',
            '/content/about',
            '/content/menu',
            '/projects/add',
            '/properties/add',
        ]))->filter(fn($_, $key) => empty($data[$key]))->first();

        return response()->json([
            'message' => 'Step marked as completed.',
            'steps' => $data,
            'progress' => $percentage,
            'continue_path' => $continuePath,
        ]);
    }


}

<?php

namespace App\Http\Controllers\Api;

use App\Models\UserStep;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StepProgressController extends Controller
{
    //
    /**
     * Display the progress of a specific step for a user.
     *
     * @param  int  $userId
     * @param  string  $stepName
     * @return \Illuminate\Http\Response
     */
public function getSteps(Request $request)
{
    $user = $request->user();
    $steps = UserStep::firstOrCreate(['user_id' => $user->id]);

    $stepMap = [
        'footer' => [
            'path' => '/content/footer',
            'text' => "قم بتخصيص التذييل الخاص بك",
        ],
        'properties' => [
            'path' => '/properties/add',
            'text' => "اضف اول عقار الآن",
        ],
    ];

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

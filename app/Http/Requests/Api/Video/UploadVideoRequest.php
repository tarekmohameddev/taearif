<?php

namespace App\Http\Requests\Api\Video;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Models\Membership;

class UploadVideoRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = auth()->id();
        $membership = Membership::where('user_id', $userId)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->with('package')
            ->first();
        $videoSizeLimit = $membership && $membership->package ? $membership->package->video_size_limit : null;
        $maxVideoSizeKB = $videoSizeLimit ? (int) ($videoSizeLimit * 1024) : null;

        $videoRule = $maxVideoSizeKB ? "required|file|max:{$maxVideoSizeKB}" : 'required|file';

        return [
            'video' => $videoRule,
            'context' => 'nullable|string|in:property,project',
        ];
    }

    public function withValidator($validator)
    {
        $userId = auth()->id();
        $membership = Membership::where('user_id', $userId)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->with('package')
            ->first();
        $videoSizeLimit = $membership && $membership->package ? $membership->package->video_size_limit : null;

        if ($videoSizeLimit) {
            $validator->after(function ($validator) use ($videoSizeLimit) {
                if (request()->hasFile('video')) {
                    $fileSizeMB = request()->file('video')->getSize() / (1024 * 1024);
                    if ($fileSizeMB > $videoSizeLimit) {
                        $validator->errors()->add('video', "The video file size ({$fileSizeMB}MB) exceeds your package limit of {$videoSizeLimit}MB.");
                    }
                }
            });
        }
    }
}

<?php

namespace App\Http\Requests\Api\Video;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Models\Membership;

class InitiateChunkedUploadRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'filename' => 'required|string',
            'content_type' => 'nullable|string',
            'total_size' => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $userId = auth()->id();
            $membership = Membership::where('user_id', $userId)
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->with('package')
                ->first();
            $videoSizeLimit = $membership && $membership->package ? $membership->package->video_size_limit : null;
            if ($videoSizeLimit && request()->has('total_size')) {
                $totalSizeMB = request()->input('total_size') / (1024 * 1024);
                if ($totalSizeMB > $videoSizeLimit) {
                    $msg = sprintf('The video file size (%.2fMB) exceeds your package limit of %sMB.', $totalSizeMB, $videoSizeLimit);
                    $validator->errors()->add('total_size', $msg);
                }
            }
        });
    }
}

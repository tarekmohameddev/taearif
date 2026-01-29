<?php

namespace App\Http\Controllers\Api\blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\blog\StoreMediaRequest;
use App\Http\Resources\Api\blog\MediaResource;
use App\Models\Api\Media;
use App\Services\AlibabaOssService;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    public function __construct(
        private AlibabaOssService $ossService
    ) {}

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $file = $request->file('file');
        $prefix = 'api-media/' . date('Y/m');
        $result = $this->ossService->uploadFile($file, $prefix);
        $path = $result['path'];
        $disk = 'oss';

        $mime = $file->getMimeType();
        $type = str_starts_with((string) $mime, 'video/') ? 'video' : 'image';

        $media = Media::create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'type' => $type,
            'mediable_id' => $request->input('mediable_id'),
            'mediable_type' => $request->input('mediable_type'),
        ]);

        return (new MediaResource($media))->response()->setStatusCode(201);
    }
}

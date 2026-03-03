<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlibabaOssService;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Api\Video\UploadVideoRequest;
use App\Http\Requests\Api\Video\InitiateChunkedUploadRequest;
use App\Http\Requests\Api\Video\UploadChunkRequest;
use App\Http\Requests\Api\Video\CompleteChunkedUploadRequest;
use App\Http\Requests\Api\Video\AbortChunkedUploadRequest;
use App\Http\Requests\Api\Video\GetSignedUploadUrlRequest;
use App\Http\Requests\Api\Video\DeleteVideoRequest;

class VideoUploadController extends Controller
{
    private $ossService;

    public function __construct(AlibabaOssService $ossService)
    {
        $this->ossService = $ossService;
    }

    /**
     * Get user's video size limit from their package
     */
    private function getUserVideoSizeLimit($userId)
    {
        $membership = Membership::where('user_id', $userId)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->with('package')
            ->first();

        return $membership->package->video_size_limit ?? null;
    }

    /**
     * Upload video directly to OSS
     */
    public function uploadVideo(UploadVideoRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        try {
            $result = $this->ossService->uploadVideo(
                $request->file('video'),
                $user->id,
                $validated['context'] ?? 'property'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Video uploaded successfully',
                'data' => $result
            ], 200);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize chunked upload
     */
    public function initiateChunkedUpload(InitiateChunkedUploadRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        try {
            $filename = 'videos/property/' . $user->id . '/' . $validated['filename'];
            $result = $this->videoService->initiateMultipartUpload(
                $filename,
                $validated['content_type'] ?? 'video/mp4'
            );

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initialize upload'
            ], 500);
        }
    }

    /**
     * Upload chunk
     */
    public function uploadChunk(UploadChunkRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->videoService->uploadVideoChunk(
                $validated['chunk_data'],
                $validated['upload_id'],
                $validated['part_number'],
                $validated['filename']
            );

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload chunk'
            ], 500);
        }
    }

    /**
     * Complete chunked upload
     */
    public function completeChunkedUpload(CompleteChunkedUploadRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->videoService->completeMultipartUpload(
                $validated['filename'],
                $validated['upload_id'],
                $validated['parts']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Video upload completed successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete upload'
            ], 500);
        }
    }

    /**
     * Abort chunked upload
     */
    public function abortChunkedUpload(AbortChunkedUploadRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $success = $this->videoService->abortMultipartUpload(
                $validated['filename'],
                $validated['upload_id']
            );

            return response()->json([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Upload aborted successfully' : 'Failed to abort upload'
            ], $success ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to abort upload'
            ], 500);
        }
    }

    /**
     * Get signed URL for direct upload
     */
    public function getSignedUploadUrl(GetSignedUploadUrlRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $user = Auth::user();
            $filename = 'videos/property/' . $user->id . '/' . $validated['filename'];
            $result = $this->videoService->getSignedUploadUrl(
                $filename,
                $validated['expires'] ?? 3600
            );

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate signed URL'
            ], 500);
        }
    }

    /**
     * Delete video
     */
    public function deleteVideo(DeleteVideoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $success = $this->videoService->deleteVideo($validated['path']);

            return response()->json([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Video deleted successfully' : 'Failed to delete video'
            ], $success ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete video'
            ], 500);
        }
    }
}

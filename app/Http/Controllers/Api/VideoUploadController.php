<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlibabaOssService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class VideoUploadController extends Controller
{
    private $ossService;

    public function __construct(AlibabaOssService $ossService)
    {
        $this->ossService = $ossService;
    }

    /**
     * Upload video directly to OSS
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'video' => 'required|file|max:51200', // 50MB max
            'context' => 'nullable|string|in:property,project,content'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $result = $this->ossService->uploadVideo(
                $request->file('video'),
                $user->id,
                $request->input('context', 'property')
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
    public function initiateChunkedUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
            'content_type' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $filename = 'videos/property/' . $user->id . '/' . $request->filename;
            
            $result = $this->videoService->initiateMultipartUpload(
                $filename,
                $request->input('content_type', 'video/mp4')
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
    public function uploadChunk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chunk_data' => 'required|string',
            'upload_id' => 'required|string',
            'part_number' => 'required|integer|min:1',
            'filename' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->videoService->uploadVideoChunk(
                $request->chunk_data,
                $request->upload_id,
                $request->part_number,
                $request->filename
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
    public function completeChunkedUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string',
            'filename' => 'required|string',
            'parts' => 'required|array',
            'parts.*.etag' => 'required|string',
            'parts.*.part_number' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->videoService->completeMultipartUpload(
                $request->filename,
                $request->upload_id,
                $request->parts
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
    public function abortChunkedUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string',
            'filename' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $this->videoService->abortMultipartUpload(
                $request->filename,
                $request->upload_id
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
    public function getSignedUploadUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
            'expires' => 'nullable|integer|min:300|max:3600'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $filename = 'videos/property/' . $user->id . '/' . $request->filename;
            
            $result = $this->videoService->getSignedUploadUrl(
                $filename,
                $request->input('expires', 3600)
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
    public function deleteVideo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $this->videoService->deleteVideo($request->path);

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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Upload a file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'context' => 'required|string',
            'sub_folder' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $context = $request->input('context');
            $subFolder = $request->input('sub_folder');

            $options = [
                'subFolder' => $subFolder,
            ];

            $path = $this->uploadService->uploadFile($file, $context, $options);

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'data' => [
                    'url' => asset($path),
                    'path' => $path,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Upload multiple files
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMultiple(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => 'required|file',
            'context' => 'required|string',
            'sub_folder' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $files = $request->file('files');
        $context = $request->input('context');
        $subFolder = $request->input('sub_folder');

        $options = [
            'subFolder' => $subFolder,
        ];

        $uploadedFiles = [];
        $failedFiles = [];
        $totalFiles = count($files);
        $successCount = 0;
        $failedCount = 0;

        // Process each file individually to allow partial success
        foreach ($files as $index => $file) {
            try {
                $path = $this->uploadService->uploadFile($file, $context, $options);
                $uploadedFiles[] = [
                    'url' => asset($path),
                    'path' => $path,
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                ];
                $successCount++;
            } catch (\Exception $e) {
                $failedFiles[] = [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
                $failedCount++;
            }
        }

        // Determine overall status
        $overallStatus = 'success';
        $message = 'Files uploaded successfully';

        if ($failedCount > 0 && $successCount > 0) {
            $overallStatus = 'partial';
            $message = "{$successCount} file(s) uploaded successfully, {$failedCount} file(s) failed";
        } elseif ($failedCount > 0 && $successCount === 0) {
            $overallStatus = 'error';
            $message = "All files failed to upload";
        }

        $response = [
            'status' => $overallStatus,
            'message' => $message,
            'data' => [
                'files' => $uploadedFiles,
                'total' => $totalFiles,
                'success' => $successCount,
                'failed' => $failedCount,
            ]
        ];

        // Include failed files details if any
        if ($failedCount > 0) {
            $response['data']['failed_files'] = $failedFiles;
        }

        // Return appropriate HTTP status code
        $httpStatus = ($overallStatus === 'error') ? 400 : (($overallStatus === 'partial') ? 207 : 200);

        return response()->json($response, $httpStatus);
    }

    /**
     * Delete a file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->input('path');
            $result = $this->uploadService->deleteFile($path);

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'File deleted successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File not found or could not be deleted'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
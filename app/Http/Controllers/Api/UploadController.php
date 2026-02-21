<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Upload\DeleteUploadFileRequest;
use App\Http\Requests\Api\Upload\UploadFileRequest;
use App\Http\Requests\Api\Upload\UploadMultipleFilesRequest;
use App\Services\UploadService;
use Illuminate\Http\Request;

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
    public function upload(UploadFileRequest $request)
    {
        try {
            $validated = $request->validated();
            $file = request()->file('file');
            $context = $validated['context'];
            $subFolder = $validated['sub_folder'] ?? null;

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
    public function uploadMultiple(UploadMultipleFilesRequest $request)
    {
        $validated = $request->validated();
        $files = request()->file('files');
        
        // Filter out null/empty file entries
        $files = array_filter($files, function($file) {
            return $file !== null && $file->isValid();
        });

        if (empty($files)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid files were provided for upload',
                'data' => [
                    'files' => [],
                    'total' => 0,
                    'success' => 0,
                    'failed' => 0,
                ]
            ], 400);
        }

        $context = $validated['context'];
        $subFolder = $validated['sub_folder'] ?? null;

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
            // Skip if file is not a valid UploadedFile instance
            if (!$file || !($file instanceof \Illuminate\Http\UploadedFile)) {
                $failedFiles[] = [
                    'index' => $index,
                    'filename' => 'unknown',
                    'error' => 'Invalid file object provided',
                ];
                $failedCount++;
                continue;
            }

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
    public function delete(DeleteUploadFileRequest $request)
    {
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
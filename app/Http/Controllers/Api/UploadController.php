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

        try {
            $files = $request->file('files');
            $context = $request->input('context');
            $subFolder = $request->input('sub_folder');

            $options = [
                'subFolder' => $subFolder,
            ];

            $uploadedFiles = [];

            foreach ($files as $file) {
                $path = $this->uploadService->uploadFile($file, $context, $options);
                $uploadedFiles[] = [
                    'url' => asset($path),
                    'path' => $path,
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Files uploaded successfully',
                'data' => [
                    'files' => $uploadedFiles
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
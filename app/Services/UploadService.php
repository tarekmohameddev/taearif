<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class UploadService
{
    /**
     * Upload a file to the specified directory
     *
     * @param UploadedFile $file
     * @param string $context The upload context (blog, property, project, etc.)
     * @param array $options Additional options for processing
     * @return string The file path
     */
    public function uploadFile(UploadedFile $file, string $context, array $options = [])
    {
        // Define allowed contexts and their specific configurations
        $allowedContexts = [
            'blog' => [
                'path' => 'blogs',
                'maxWidth' => 1200,
                'maxSize' => 2048, // 2MB
                'allowedTypes' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
            'property' => [
                'path' => 'properties',
                'maxWidth' => 1600,
                'maxSize' => 5120, // 5MB
                'allowedTypes' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
            'project' => [
                'path' => 'projects',
                'maxWidth' => 1600,
                'maxSize' => 5120, // 5MB
                'allowedTypes' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
            'profile' => [
                'path' => 'profiles',
                'maxWidth' => 500,
                'maxSize' => 1024, // 1MB
                'allowedTypes' => ['jpg', 'jpeg', 'png'],
            ],
            'logo' => [
                'path' => 'logos',
                'maxWidth' => 400,
                'maxSize' => 1024, // 1MB
                'allowedTypes' => ['jpg', 'jpeg', 'png', 'svg'],
            ],
            'content' => [
                'path' => 'content',
                'maxWidth' => 1600,
                'maxSize' => 3072, // 3MB
                'allowedTypes' => ['jpg', 'jpeg', 'png', 'webp', 'svg'],
            ],
            'template' => [
                'path' => 'templates',
                'maxWidth' => 1200,
                'maxSize' => 2048, // 2MB
                'allowedTypes' => ['jpg', 'jpeg', 'png'],
            ],
            'app' => [
                'path' => 'apps',
                'maxWidth' => 800,
                'maxSize' => 1024, // 1MB
                'allowedTypes' => ['jpg', 'jpeg', 'png', 'svg'],
            ],
        ];

        // Validate context
        if (!array_key_exists($context, $allowedContexts)) {
            throw new \InvalidArgumentException("Invalid upload context: {$context}");
        }

        $config = $allowedContexts[$context];
        
        // Validate file type
        $extension = $file->getClientOriginalExtension();
        if (!in_array(strtolower($extension), $config['allowedTypes'])) {
            throw new \InvalidArgumentException("Invalid file type: {$extension}");
        }

        // Validate file size
        if ($file->getSize() > $config['maxSize'] * 1024) {
            throw new \InvalidArgumentException("File size exceeds the maximum allowed size");
        }

        // Generate a unique filename
        $filename = Str::uuid() . '.' . $extension;
        
        // Determine the storage path
        $subFolder = $options['subFolder'] ?? '';
        $path = $config['path'] . ($subFolder ? '/' . $subFolder : '');
        
        // Process image if it's not an SVG
        if ($extension !== 'svg') {
            // Create the directory if it doesn't exist
            if (!Storage::exists("public/{$path}")) {
                Storage::makeDirectory("public/{$path}");
            }
            
            // Process the image with Intervention Image
            $image = Image::make($file);
            
            // Resize if needed
            if ($image->width() > $config['maxWidth']) {
                $image->resize($config['maxWidth'], null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Apply additional processing based on context
            if ($context === 'profile' || $context === 'logo') {
                // Optimize for profile pictures and logos
                $image->encode($extension, 85);
            } else {
                // Standard optimization for other images
                $image->encode($extension, 80);
            }
            
            // Save the processed image
            $image->save(storage_path("app/public/{$path}/{$filename}"));
            
            return "storage/{$path}/{$filename}";
        } else {
            // For SVG files, store directly
            $file->storeAs("public/{$path}", $filename);
            return "storage/{$path}/{$filename}";
        }
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path)
    {
        // Remove "storage/" prefix if present
        $storagePath = str_replace('storage/', '', $path);
        
        if (Storage::exists("public/{$storagePath}")) {
            return Storage::delete("public/{$storagePath}");
        }
        
        return false;
    }
}
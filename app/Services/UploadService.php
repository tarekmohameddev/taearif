<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class UploadService
{
    public function uploadFile(UploadedFile $file, string $context, array $options = [])
    {
        $allowedContexts = [
            'blog' => ['path' => 'blogs', 'maxWidth' => 1200, 'maxSize' => 2048, 'allowedTypes' => ['jpg', 'jpeg', 'png', 'webp']],
            'property' => ['path' => 'properties', 'maxWidth' => 1600, 'maxSize' => 5120, 'allowedTypes' => ['jpg', 'jpeg', 'png', 'webp']],
            'project' => ['path' => 'projects', 'maxWidth' => 1600, 'maxSize' => 5120, 'allowedTypes' => ['jpg', 'jpeg', 'png', 'webp']],
            'profile' => ['path' => 'profiles', 'maxWidth' => 500, 'maxSize' => 1024, 'allowedTypes' => ['jpg', 'jpeg', 'png']],
            'logo' => ['path' => 'logos', 'maxWidth' => 400, 'maxSize' => 1024, 'allowedTypes' => ['jpg', 'jpeg', 'png', 'svg']],
            'content' => ['path' => 'content', 'maxWidth' => 1600, 'maxSize' => 3072, 'allowedTypes' => ['jpg', 'jpeg', 'png', 'webp', 'svg']],
            'template' => ['path' => 'templates', 'maxWidth' => 1200, 'maxSize' => 2048, 'allowedTypes' => ['jpg', 'jpeg', 'png']],
            'app' => ['path' => 'apps', 'maxWidth' => 800, 'maxSize' => 1024, 'allowedTypes' => ['jpg', 'jpeg', 'png', 'svg']],
        ];

        if (!array_key_exists($context, $allowedContexts)) {
            throw new \InvalidArgumentException("Invalid upload context: {$context}");
        }

        $config = $allowedContexts[$context];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $config['allowedTypes'])) {
            throw new \InvalidArgumentException("Invalid file type: {$extension}");
        }

        // Check if file is empty
        $fileSize = $file->getSize();
        if ($fileSize === 0 || $fileSize === false) {
            throw new \InvalidArgumentException("The uploaded file is empty or invalid.");
        }

        if ($fileSize > $config['maxSize'] * 1024) {
            throw new \InvalidArgumentException("File size exceeds the maximum allowed size");
        }

        // Validate MIME type for image files (except SVG)
        if ($extension !== 'svg') {
            $mimeType = $file->getMimeType();
            $allowedMimeTypes = [
                'jpg' => ['image/jpeg', 'image/jpg'],
                'jpeg' => ['image/jpeg', 'image/jpg'],
                'png' => ['image/png'],
                'webp' => ['image/webp'],
                'gif' => ['image/gif'],
                'bmp' => ['image/bmp', 'image/x-ms-bmp'],
            ];

            if (isset($allowedMimeTypes[$extension]) && !in_array($mimeType, $allowedMimeTypes[$extension])) {
                throw new \InvalidArgumentException("Invalid MIME type: {$mimeType}. Expected image file but got {$mimeType}.");
            }

            // Additional check: verify the file is actually a valid image
            if (!in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/bmp', 'image/x-ms-bmp'])) {
                throw new \InvalidArgumentException("Unsupported image type {$mimeType}. GD driver is only able to decode JPG, PNG, GIF, BMP or WebP files.");
            }
        }

        $filename = Str::uuid() . '.' . $extension;
        $subFolder = $options['subFolder'] ?? '';
        $path = $config['path'] . ($subFolder ? '/' . $subFolder : '');
        $fullPath = public_path($path);

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        if ($extension !== 'svg') {
            try {
                $image = Image::make($file);

                if ($image->width() > $config['maxWidth']) {
                    $image->resize($config['maxWidth'], null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                if (in_array($context, ['profile', 'logo'])) {
                    $image->encode($extension, 85);
                } else {
                    $image->encode($extension, 80);
                }

                $image->save("{$fullPath}/{$filename}");
            } catch (\Exception $e) {
                // Handle image processing errors gracefully
                if (strpos($e->getMessage(), 'Unsupported image type') !== false || 
                    strpos($e->getMessage(), 'decode') !== false) {
                    throw new \InvalidArgumentException("The uploaded file is not a valid image file. Please ensure the file is a valid JPG, PNG, GIF, BMP or WebP image.");
                }
                throw new \InvalidArgumentException("Failed to process image: " . $e->getMessage());
            }
        } else {
            $file->move($fullPath, $filename);
        }

        return "{$path}/{$filename}";
    }

    public function deleteFile(string $path)
    {
        $fullPath = public_path($path);

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }
}

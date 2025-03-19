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

        if ($file->getSize() > $config['maxSize'] * 1024) {
            throw new \InvalidArgumentException("File size exceeds the maximum allowed size");
        }

        $filename = Str::uuid() . '.' . $extension;
        $subFolder = $options['subFolder'] ?? '';
        $path = $config['path'] . ($subFolder ? '/' . $subFolder : '');
        $fullPath = public_path($path);

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        if ($extension !== 'svg') {
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

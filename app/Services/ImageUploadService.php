<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Upload article image
     *
     * @param UploadedFile $file
     * @param string|null $oldPath
     * @return string
     */
    public function uploadArticleImage(UploadedFile $file, ?string $oldPath = null): string
    {
        // Validate file
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid image type. Allowed types: JPG, JPEG, PNG, WEBP');
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('Image size exceeds maximum allowed size of 5MB');
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $directory = public_path('assets/front/img/admin-articles');

        // Create directory if it doesn't exist
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Move file
        $file->move($directory, $filename);
        $imagePath = 'assets/front/img/admin-articles/' . $filename;

        // Delete old image if provided
        if ($oldPath && File::exists(public_path($oldPath))) {
            File::delete(public_path($oldPath));
        }

        return $imagePath;
    }
}

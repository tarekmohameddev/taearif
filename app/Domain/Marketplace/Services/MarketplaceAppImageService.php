<?php

namespace App\Domain\Marketplace\Services;

use App\Exceptions\Marketplace\ImageUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Marketplace App Image Service
 *
 * Handles image upload, validation, and deletion for marketplace apps
 */
class MarketplaceAppImageService
{
    /**
     * Base directory for marketplace app images
     */
    private const IMAGE_DIRECTORY = 'assets/front/img/marketplace-apps';

    /**
     * Maximum file size in KB (2MB)
     */
    private const MAX_FILE_SIZE = 2048;

    /**
     * Allowed MIME types
     */
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];

    /**
     * Handle image upload or URL
     *
     * @param UploadedFile|null $file
     * @param string|null $url
     * @param string|null $oldPath Old image path to delete if new image is uploaded
     * @return string Image path or URL
     * @throws ImageUploadException
     */
    public function handleImageUpload(?UploadedFile $file = null, ?string $url = null, ?string $oldPath = null): string
    {
        if ($file) {
            return $this->uploadFile($file, $oldPath);
        }

        if ($url) {
            return $this->validateUrl($url);
        }

        throw new ImageUploadException(
            'Either image file or image URL must be provided',
            'IMAGE_UPLOAD_FAILED',
            422
        );
    }

    /**
     * Upload and save image file
     *
     * @param UploadedFile $file
     * @param string|null $oldPath
     * @return string Relative path to uploaded image
     * @throws ImageUploadException
     */
    public function uploadFile(UploadedFile $file, ?string $oldPath = null): string
    {
        // Validate file
        $this->validateImage($file);

        try {
            // Generate unique filename using UUID
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $directory = public_path(self::IMAGE_DIRECTORY);

            // Create directory if it doesn't exist
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Move file
            $file->move($directory, $filename);
            $imagePath = self::IMAGE_DIRECTORY . '/' . $filename;

            // Delete old image if provided
            if ($oldPath) {
                $this->deleteImage($oldPath);
            }

            return $imagePath;
        } catch (\Exception $e) {
            throw new ImageUploadException(
                'Failed to upload image: ' . $e->getMessage(),
                'IMAGE_UPLOAD_FAILED',
                422
            );
        }
    }

    /**
     * Validate image file
     *
     * @param UploadedFile $file
     * @throws ImageUploadException
     */
    public function validateImage(UploadedFile $file): void
    {
        // Check file size
        $fileSizeKB = $file->getSize() / 1024;
        if ($fileSizeKB > self::MAX_FILE_SIZE) {
            throw new ImageUploadException(
                'Image size exceeds maximum allowed size of ' . self::MAX_FILE_SIZE . ' KB',
                'IMAGE_UPLOAD_FAILED',
                422
            );
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new ImageUploadException(
                'Invalid image type. Allowed types: JPG, JPEG, PNG',
                'IMAGE_UPLOAD_FAILED',
                422
            );
        }

        // Validate image content (not just MIME type)
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new ImageUploadException(
                'Invalid image file. File does not appear to be a valid image.',
                'IMAGE_UPLOAD_FAILED',
                422
            );
        }

        // Verify it's actually an image by checking image type
        $imageType = $imageInfo[2];
        if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
            throw new ImageUploadException(
                'Invalid image format. Only JPEG and PNG are allowed.',
                'IMAGE_UPLOAD_FAILED',
                422
            );
        }
    }

    /**
     * Validate URL format
     *
     * @param string $url
     * @return string Validated URL
     * @throws ImageUploadException
     */
    public function validateUrl(string $url): string
    {
        if (empty($url)) {
            throw new ImageUploadException(
                'Image URL cannot be empty',
                'IMAGE_UPLOAD_FAILED',
                422
            );
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ImageUploadException(
                'Invalid image URL format',
                'IMAGE_UPLOAD_FAILED',
                422
            );
        }

        // Check if URL is HTTP or HTTPS
        if (!preg_match('/^https?:\/\//', $url)) {
            throw new ImageUploadException(
                'Image URL must start with http:// or https://',
                'IMAGE_UPLOAD_FAILED',
                422
            );
        }

        return $url;
    }

    /**
     * Delete image file
     *
     * @param string $path Image path (relative or absolute)
     * @return bool
     */
    public function deleteImage(string $path): bool
    {
        // Only delete if it's a local file in our directory
        if (strpos($path, self::IMAGE_DIRECTORY) === false) {
            return false;
        }

        try {
            $fullPath = public_path($path);
            if (File::exists($fullPath)) {
                return File::delete($fullPath);
            }
        } catch (\Exception $e) {
            // Log error but don't throw - file deletion failure shouldn't break the flow
            logger()->warning('Failed to delete marketplace app image', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Check if path is a local file (not external URL)
     *
     * @param string $path
     * @return bool
     */
    public function isLocalFile(string $path): bool
    {
        return strpos($path, 'http://') !== 0 && strpos($path, 'https://') !== 0;
    }
}


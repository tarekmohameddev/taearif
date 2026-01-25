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

        // Check if file upload was successful
        if (!$file->isValid()) {
            $errorCode = $file->getError();
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            $errorMessage = $errorMessages[$errorCode] ?? 'File upload failed with unknown error.';
            throw new \InvalidArgumentException("File upload failed: {$errorMessage}");
        }

        // Check if file is empty
        $fileSize = $file->getSize();
        if ($fileSize === 0 || $fileSize === false) {
            throw new \InvalidArgumentException("The uploaded file is empty. Please ensure you are uploading a valid file with content.");
        }

        if ($fileSize > $config['maxSize'] * 1024) {
            throw new \InvalidArgumentException("File size exceeds the maximum allowed size of " . $config['maxSize'] . " KB.");
        }

        // Validate MIME type for image files (except SVG)
        if ($extension !== 'svg') {
            $mimeType = $file->getMimeType();
            
            // Check for empty/invalid file MIME type first
            if ($mimeType === 'application/x-empty' || empty($mimeType)) {
                throw new \InvalidArgumentException("The uploaded file appears to be empty or corrupted. Please ensure you are uploading a valid image file (JPG, PNG, GIF, BMP, or WebP) with actual content.");
            }
            
            // Check for generic binary type only if file size is suspiciously small
            if ($mimeType === 'application/octet-stream' && $fileSize < 100) {
                throw new \InvalidArgumentException("The uploaded file appears to be empty or corrupted. Please ensure you are uploading a valid image file (JPG, PNG, GIF, BMP, or WebP) with actual content.");
            }
            
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
                throw new \InvalidArgumentException("Unsupported image type: {$mimeType}. Only JPG, PNG, GIF, BMP, and WebP image files are supported.");
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
                // Verify file has actual content before processing
                $fileContent = file_get_contents($file->getRealPath());
                if (empty($fileContent) || strlen($fileContent) === 0) {
                    throw new \InvalidArgumentException("The uploaded file is empty or contains no data. Please upload a valid image file.");
                }
                
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
            } catch (\InvalidArgumentException $e) {
                // Re-throw our custom exceptions
                throw $e;
            } catch (\Exception $e) {
                // Handle image processing errors gracefully
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'Unsupported image type') !== false || 
                    strpos($errorMessage, 'decode') !== false ||
                    strpos($errorMessage, 'application/x-empty') !== false) {
                    throw new \InvalidArgumentException("The uploaded file is not a valid image file or is empty. Please ensure the file is a valid JPG, PNG, GIF, BMP or WebP image with actual content.");
                }
                throw new \InvalidArgumentException("Failed to process image: " . $errorMessage);
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

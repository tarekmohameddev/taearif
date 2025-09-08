<?php

namespace App\Services;

use OSS\OssClient;
use OSS\Core\OssException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Models\Membership;

class AlibabaOssService
{
    private $client;
    private $bucket;
    private $endpoint;

    public function __construct()
    {
        // Use env() directly for debugging
        $this->bucket = env('OSS_BUCKET');
        $this->endpoint = env('OSS_ENDPOINT');
        
        $accessKeyId = env('OSS_ACCESS_KEY_ID');
        $accessKeySecret = env('OSS_ACCESS_KEY_SECRET');
        
        // Debug output
        \Log::info('OSS Config Debug', [
            'bucket' => $this->bucket,
            'endpoint' => $this->endpoint,
            'key_exists' => !empty($accessKeyId),
            'secret_exists' => !empty($accessKeySecret),
        ]);
        
        // Check each required field individually
        if (empty($accessKeyId)) {
            throw new \Exception('OSS_ACCESS_KEY_ID is missing from .env file');
        }
        
        if (empty($accessKeySecret)) {
            throw new \Exception('OSS_ACCESS_KEY_SECRET is missing from .env file');
        }
        
        if (empty($this->endpoint)) {
            throw new \Exception('OSS_ENDPOINT is missing from .env file');
        }
        
        if (empty($this->bucket)) {
            throw new \Exception('OSS_BUCKET is missing from .env file');
        }
        
        try {
            $this->client = new OssClient(
                $accessKeyId,
                $accessKeySecret,
                $this->endpoint
            );
        } catch (OssException $e) {
            throw new \Exception('Failed to initialize OSS client: ' . $e->getMessage());
        }
    }

    /**
     * Upload file to Alibaba Cloud OSS
     */
    public function uploadFile(UploadedFile $file, string $path = '', string $visibility = 'public'): array
    {
        try {
            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = $path ? $path . '/' . Str::uuid() . '.' . $extension : Str::uuid() . '.' . $extension;
            
            // Upload file
            $result = $this->client->uploadFile($this->bucket, $filename, $file->getPathname());
            
            // Get public URL
            $url = $this->getPublicUrl($filename);
            
            return [
                'success' => true,
                'path' => $filename,
                'url' => $url,
                'size' => $file->getSize(),
                'filename' => $filename
            ];
            
        } catch (OssException $e) {
            throw new \Exception('Failed to upload file to OSS: ' . $e->getMessage());
        }
    }

    /**
     * Upload video with size validation
     */
    public function uploadVideo(UploadedFile $file, int $userId, string $context = 'property'): array
    {
        // Check user's video size limit
        $maxSize = $this->getUserVideoSizeLimit($userId);
        
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException(
                "Video size exceeds your package limit of " . $this->formatBytes($maxSize)
            );
        }

        $path = 'videos/' . $context . '/' . $userId;
        return $this->uploadFile($file, $path);
    }

    /**
     * Delete file from OSS
     */
    public function deleteFile(string $filename): bool
    {
        try {
            $this->client->deleteObject($this->bucket, $filename);
            return true;
        } catch (OssException $e) {
            return false;
        }
    }

    /**
     * Get public URL for file
     */
    public function getPublicUrl(string $filename): string
    {
        return rtrim($this->endpoint, '/') . '/' . $this->bucket . '/' . $filename;
    }

    /**
     * Get user's video size limit from their membership package
     */
    private function getUserVideoSizeLimit(int $userId): int
    {
        $membership = Membership::where('user_id', $userId)
            ->where('status', 1)
            ->with('package')
            ->orderBy('id', 'desc')
            ->first();

        if (!$membership || !$membership->package) {
            // Default limit if no active membership
            return 50 * 1024 * 1024; // 50MB
        }

        $limit = $membership->package->video_size_limit;
        
        // Convert MB to bytes if needed
        if ($limit < 1024) {
            $limit = $limit * 1024 * 1024; // Assume it's in MB
        }

        return $limit;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

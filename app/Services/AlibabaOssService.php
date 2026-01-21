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
        // Lazy initialization - only initialize when actually used
        // Use config() instead of env() to work with config cache
        $this->bucket = config('filesystems.disks.oss.bucket');
        $this->endpoint = config('filesystems.disks.oss.endpoint');
    }
    
    /**
     * Initialize the OSS client (lazy loading)
     */
    private function initializeClient()
    {
        if ($this->client !== null) {
            return;
        }
        
        // Use config() instead of env() to work with config cache
        $accessKeyId = config('filesystems.disks.oss.key');
        $accessKeySecret = config('filesystems.disks.oss.secret');
       
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
        $this->initializeClient();
        
        try {
            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = $path ? $path . '/' . Str::uuid() . '.' . $extension : Str::uuid() . '.' . $extension;
            
            // Determine content type based on file extension
            $contentType = $this->getContentType($extension);
            
            // Upload file with public read ACL and proper content type
            $options = [
                OssClient::OSS_HEADERS => [
                    'x-oss-object-acl' => OssClient::OSS_ACL_TYPE_PUBLIC_READ,
                    'Content-Type' => $contentType
                ]
            ];
            
            $result = $this->client->uploadFile($this->bucket, $filename, $file->getPathname(), $options);
            
            // Get public URL (using third-level domain)
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
     * Get content type based on file extension
     */
    private function getContentType(string $extension): string
    {
        $contentTypes = [
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            '3gp' => 'video/3gpp',
            'm4v' => 'video/x-m4v',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
        ];
        
        return $contentTypes[strtolower($extension)] ?? 'application/octet-stream';
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
        $this->initializeClient();
        
        try {
            $this->client->deleteObject($this->bucket, $filename);
            return true;
        } catch (OssException $e) {
            return false;
        }
    }

    /**
     * Get public URL for file (updated to use third-level domain)
     */
    public function getPublicUrl(string $filename): string
    {
        // Use third-level domain format: bucket.oss-region.aliyuncs.com
        $bucket = $this->bucket;
        // Use config() instead of env() to work with config cache
        $region = config('filesystems.disks.oss.region', 'me-central-1');
        
        return "https://{$bucket}.oss-{$region}.aliyuncs.com/{$filename}";
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

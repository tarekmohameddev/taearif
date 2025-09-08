<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Membership;
use App\Models\Package;

class AlibabaOssVideoService
{
    private $disk;
    private $bucket;

    public function __construct()
    {
        $this->disk = Storage::disk('oss');
        $this->bucket = config('filesystems.disks.oss.bucket');
    }

    /**
     * Upload video to Alibaba Cloud OSS
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

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = 'videos/' . $context . '/' . $userId . '/' . Str::uuid() . '.' . $extension;
        
        // Upload to OSS
        $path = $this->disk->putFileAs('', $file, $filename, 'public');
        
        if (!$path) {
            throw new \Exception('Failed to upload video to OSS');
        }

        // Get public URL
        $url = $this->disk->url($path);

        return [
            'path' => $path,
            'url' => $url,
            'size' => $file->getSize(),
            'filename' => $filename
        ];
    }

    /**
     * Upload video in chunks for resumable uploads
     */
    public function uploadVideoChunk(string $chunkData, string $uploadId, int $partNumber, string $filename): array
    {
        // For chunked uploads, we'll use multipart upload
        $tempFile = tmpfile();
        fwrite($tempFile, base64_decode($chunkData));
        rewind($tempFile);
        
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        
        try {
            // Upload chunk to OSS
            $result = $this->disk->getDriver()->getAdapter()->getClient()->uploadPart([
                'Bucket' => $this->bucket,
                'Key' => $filename,
                'UploadId' => $uploadId,
                'PartNumber' => $partNumber,
                'Body' => fopen($tempPath, 'r')
            ]);

            return [
                'success' => true,
                'etag' => $result['ETag'],
                'part_number' => $partNumber
            ];
        } finally {
            fclose($tempFile);
        }
    }

    /**
     * Initialize multipart upload
     */
    public function initiateMultipartUpload(string $filename, string $contentType = 'video/mp4'): array
    {
        $result = $this->disk->getDriver()->getAdapter()->getClient()->createMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $filename,
            'ContentType' => $contentType,
            'ACL' => 'public-read'
        ]);

        return [
            'upload_id' => $result['UploadId'],
            'filename' => $filename
        ];
    }

    /**
     * Complete multipart upload
     */
    public function completeMultipartUpload(string $filename, string $uploadId, array $parts): array
    {
        $result = $this->disk->getDriver()->getAdapter()->getClient()->completeMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $filename,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $parts
            ]
        ]);

        return [
            'success' => true,
            'url' => $this->disk->url($filename),
            'path' => $filename
        ];
    }

    /**
     * Abort multipart upload
     */
    public function abortMultipartUpload(string $filename, string $uploadId): bool
    {
        try {
            $this->disk->getDriver()->getAdapter()->getClient()->abortMultipartUpload([
                'Bucket' => $this->bucket,
                'Key' => $filename,
                'UploadId' => $uploadId
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete video from OSS
     */
    public function deleteVideo(string $path): bool
    {
        try {
            return $this->disk->delete($path);
        } catch (\Exception $e) {
            return false;
        }
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

    /**
     * Get signed URL for direct upload (alternative approach)
     */
    public function getSignedUploadUrl(string $filename, int $expires = 3600): array
    {
        $client = $this->disk->getDriver()->getAdapter()->getClient();
        
        $command = $client->getCommand('PutObject', [
            'Bucket' => $this->bucket,
            'Key' => $filename,
            'ACL' => 'public-read'
        ]);

        $request = $client->createPresignedRequest($command, "+{$expires} seconds");

        return [
            'upload_url' => (string) $request->getUri(),
            'filename' => $filename,
            'expires_in' => $expires
        ];
    }
}

<?php

namespace App\Http\Resources\Api\Blog;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $url = null;
        if ($this->path) {
            if ($this->disk === 'oss') {
                $url = 'https://' . config('filesystems.disks.oss.bucket') . '.oss-' . config('filesystems.disks.oss.region', 'me-central-1') . '.aliyuncs.com/' . ltrim($this->path, '/');
            } else {
                $url = Storage::disk($this->disk ?? 'public')->url($this->path);
            }
        }

        return [
            'id' => $this->id,
            'url' => $url,
            'type' => $this->type,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

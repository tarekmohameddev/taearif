<?php

namespace App\Services\TenantWebsite;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\TenantMedia;
use App\Models\User;

class MediaService
{
    public function upload(User $tenant, UploadedFile $file): TenantMedia
    {
        $disk = 'public';
        $path = 'tenant/' . $tenant->username . '/' . date('Y/m');
        $stored = $file->store($path, $disk);
        $url = Storage::disk($disk)->url($stored);
        return TenantMedia::create([
            'user_id' => $tenant->id,
            'disk' => $disk,
            'path' => $stored,
            'url' => url($url),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'meta' => [
                'original_name' => $file->getClientOriginalName(),
            ],
        ]);
    }
}



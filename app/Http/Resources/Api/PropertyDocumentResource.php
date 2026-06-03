<?php

namespace App\Http\Resources\Api;

use App\Services\Property\PropertyDocumentService;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyDocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        $doc = $this->resource;
        $service = app(PropertyDocumentService::class);

        return [
            'id' => $doc->id,
            'type' => $doc->type,
            'title' => $doc->title,
            'content' => $doc->content,
            'attachments' => $service->attachmentUrls($doc->attachments ?? []),
            'meta' => $doc->meta,
            'created_by' => $doc->author ? [
                'id' => $doc->author->id,
                'name' => trim(($doc->author->first_name ?? '') . ' ' . ($doc->author->last_name ?? '')) ?: $doc->author->username,
            ] : null,
            'created_at' => $doc->created_at?->toISOString(),
        ];
    }
}

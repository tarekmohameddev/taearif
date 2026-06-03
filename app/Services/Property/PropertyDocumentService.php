<?php

namespace App\Services\Property;

use App\Models\Property\PropertyDocument;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyDocumentService
{
    public function listNotes(Property $property, int $perPage = 20)
    {
        return PropertyDocument::query()
            ->where('property_id', $property->id)
            ->where('type', 'note')
            ->with('author:id,username,first_name,last_name')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function listArchive(Property $property, int $perPage = 20)
    {
        return PropertyDocument::query()
            ->where('property_id', $property->id)
            ->whereIn('type', ['deed', 'meter', 'document'])
            ->with('author:id,username,first_name,last_name')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function storeNote(Property $property, string $note, array $attachments = [], ?int $actorId = null): PropertyDocument
    {
        return PropertyDocument::create([
            'property_id' => $property->id,
            'type' => 'note',
            'content' => $note,
            'attachments' => $this->storeAttachments($attachments),
            'created_by' => $actorId,
        ]);
    }

    public function storeArchiveItem(
        Property $property,
        string $type,
        ?string $title,
        ?string $content,
        array $attachments = [],
        ?array $meta = null,
        ?int $actorId = null,
    ): PropertyDocument {
        return PropertyDocument::create([
            'property_id' => $property->id,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'attachments' => $this->storeAttachments($attachments),
            'meta' => $meta,
            'created_by' => $actorId,
        ]);
    }

    /**
     * @param  array<int, UploadedFile|string>  $attachments
     */
    private function storeAttachments(array $attachments): array
    {
        $stored = [];

        foreach ($attachments as $file) {
            if ($file instanceof UploadedFile) {
                $path = $file->store('property-docs', 'public');
                $stored[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            } elseif (is_string($file) && $file !== '') {
                $stored[] = [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => null,
                ];
            }
        }

        return $stored;
    }

    public function attachmentUrls(array $attachments): array
    {
        return collect($attachments)->map(function ($item) {
            $path = $item['path'] ?? '';
            $url = Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : Storage::disk('public')->url($path);

            return array_merge($item, ['url' => $url]);
        })->all();
    }
}

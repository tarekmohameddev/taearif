<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

final class WaTemplateResource extends JsonResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'meta_template_id' => $this->meta_template_id,
            'name' => $this->name,
            'status' => $this->status,
            'category' => $this->category,
            'language' => $this->language,
            'components' => $this->components ?? [],
            'variables' => $this->variables ?? [],
            'namespace' => $this->namespace,
            'synced_at' => $this->synced_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

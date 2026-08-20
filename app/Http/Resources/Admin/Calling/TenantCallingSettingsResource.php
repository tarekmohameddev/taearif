<?php

namespace App\Http\Resources\Admin\Calling;

use Illuminate\Http\Resources\Json\JsonResource;

class TenantCallingSettingsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                          => $this->id,
            'tenant_id'                   => $this->tenant_id,
            'enabled'                     => $this->enabled,
            'record_by_default'           => $this->record_by_default,
            'play_recording_announcement' => $this->play_recording_announcement,
            'max_channels'                => $this->max_channels,
            'updated_at'                  => $this->updated_at?->toIso8601String(),
        ];
    }
}

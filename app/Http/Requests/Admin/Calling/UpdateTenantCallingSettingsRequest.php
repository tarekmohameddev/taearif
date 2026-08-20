<?php

namespace App\Http\Requests\Admin\Calling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantCallingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled'                     => ['sometimes', 'boolean'],
            'record_by_default'           => ['sometimes', 'boolean'],
            'play_recording_announcement' => ['sometimes', 'boolean'],
            'max_channels'                => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}

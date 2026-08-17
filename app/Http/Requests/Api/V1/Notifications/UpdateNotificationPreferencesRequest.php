<?php

namespace App\Http\Requests\Api\V1\Notifications;

use App\Domain\Notifications\NotificationPreferencesService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return collect(NotificationPreferencesService::KEYS)
            ->mapWithKeys(fn (string $key) => [$key => ['sometimes', 'boolean']])
            ->all();
    }
}

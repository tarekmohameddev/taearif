<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreArchiveItemRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['deed', 'meter', 'document'])],
            'title' => 'nullable|string|max:191',
            'content' => 'nullable|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'meta' => 'nullable|array',
            'meta.deed_number' => 'nullable|string|max:64',
            'meta.meter_kind' => ['nullable', Rule::in(['water', 'electricity'])],
            'meta.meter_number' => 'nullable|string|max:64',
            'meta.reading' => 'nullable|string|max:64',
            'meta.reading_date' => 'nullable|date',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = $this->input('type');
            $meta = $this->input('meta', []) ?? [];

            if ($type === 'deed') {
                $hasData = $this->filledString($meta['deed_number'] ?? null)
                    || $this->filledString($this->input('content'))
                    || $this->hasAttachments();

                if (! $hasData) {
                    $validator->errors()->add('type', 'Deed requires deed number, content, or at least one attachment.');
                }
            }

            if ($type === 'meter') {
                $meterKind = $meta['meter_kind'] ?? null;
                if (! in_array($meterKind, ['water', 'electricity'], true)) {
                    $validator->errors()->add('meta.meter_kind', 'Meter kind is required (water or electricity).');
                }

                $hasData = $this->filledString($meta['meter_number'] ?? null)
                    || $this->filledString($meta['reading'] ?? null)
                    || $this->hasAttachments();

                if (! $hasData) {
                    $validator->errors()->add('type', 'Meter requires meter number, reading, or at least one attachment.');
                }
            }

            if ($type === 'document') {
                $hasData = $this->filledString($this->input('title'))
                    || $this->filledString($this->input('content'))
                    || $this->hasAttachments();

                if (! $hasData) {
                    $validator->errors()->add('type', 'Document requires title, content, or at least one attachment.');
                }
            }
        });
    }

    private function filledString(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function hasAttachments(): bool
    {
        $attachments = $this->file('attachments', []);

        if (! is_array($attachments)) {
            return false;
        }

        return count(array_filter($attachments)) > 0;
    }
}

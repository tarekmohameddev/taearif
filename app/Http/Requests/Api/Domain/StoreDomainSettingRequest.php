<?php

namespace App\Http\Requests\Api\Domain;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\ValidApexDomain;
use App\Support\ApexDomainValidator;

class StoreDomainSettingRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $customName = $this->input('custom_name');
        if (! is_string($customName)) {
            return;
        }

        $this->merge([
            'custom_name' => ApexDomainValidator::normalize($customName),
        ]);
    }

    public function rules()
    {
        return [
            'custom_name' => [
                'required',
                'string',
                'max:255',
                new ValidApexDomain(),
            ],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        $fieldErrors = [];

        foreach ($errors->getMessages() as $field => $messages) {
            foreach ($messages as $message) {
                $fieldErrors[] = [
                    'field' => $field,
                    'message' => $message,
                ];
            }
        }

        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $fieldErrors,
        ], 422));
    }
}

<?php

namespace App\Http\Requests\Api\Domain;

use App\Http\Requests\Api\BaseApiFormRequest;

class EnableDomainWwwRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Ownership is enforced in the controller (tenant-scoped firstOrFail).
            'id' => ['required', 'integer'],
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

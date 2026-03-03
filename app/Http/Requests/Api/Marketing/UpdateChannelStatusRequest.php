<?php

namespace App\Http\Requests\Api\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChannelStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'is_connected' => 'required|boolean',
            'is_verified' => 'sometimes|boolean',
        ];
    }
}

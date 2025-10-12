<?php

namespace App\Http\Requests\OwnerRental;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateOwnerRentalRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        $ownerRentalId = $this->route('id');

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('owner_rentals', 'email')->ignore($ownerRentalId),
                Rule::unique('users', 'email'),
            ],
            'phone' => 'sometimes|required|string|max:20',
            'password' => ['sometimes', 'nullable', 'string', Password::min(8)],
            'id_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Owner rental name is required',
            'email.required' => 'Email address is required',
            'email.unique' => 'This email address is already in use',
            'phone.required' => 'Phone number is required',
            'password.min' => 'Password must be at least 8 characters',
        ];
    }
}


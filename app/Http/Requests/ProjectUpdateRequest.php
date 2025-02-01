<?php

namespace App\Http\Requests;

use App\Models\User\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProjectUpdateRequest extends FormRequest
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
        $userId = Auth::guard('web')->user()->id;




        $rules = [
            'featured_image' => 'mimes:png,jpg,jpeg,svg',
            'min_price' => 'required|numeric',
            'featured' => 'sometimes',
            'status' => 'required',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/']

        ];



        $languages = Language::where('user_id', $userId)->get();

        foreach ($languages as $language) {
            $rules[$language->code . '_title'] = 'required|max:255';
            $rules[$language->code . '_address'] = 'required'; 
            $rules[$language->code . '_description'] = 'required|min:15';


            $rules[$language->code . '_label'] = 'array';
        }

        return $rules;
    }
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        $message = [];

        $userId = Auth::guard('web')->user()->id;

        $languages = Language::where('user_id', $userId)->get();

        foreach ($languages as $language) {

            $message[$language->code . '_title.required'] = 'The title field is required for ' . $language->name . ' language.';
            $message[$language->code . '_address.required'] = 'The address field is required for ' . $language->name . ' language.';
            $message[$language->code . '_description.required'] = 'The description field is required for ' . $language->name . ' language.';
            $message[$language->code . '_description.min'] = 'The description  must be at least :min characters for ' . $language->name . ' language.';
            $message[$language->code . '_label.max'] = 'Additional Features for ' . $language->name . ' language shall not exceed :max.';
        }

        return $message;
    }
}

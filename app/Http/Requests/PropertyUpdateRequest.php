<?php

namespace App\Http\Requests;

use App\Models\User\BasicSetting;
use App\Models\User\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PropertyUpdateRequest extends FormRequest
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
            // 'featured_image' => 'required|mimes:png,jpg,jpeg,svg,webp',
            // 'floor_planning_image' => 'required|mimes:png,jpg,jpeg,svg,webp',
            'price' => 'nullable|numeric',
            'beds' => 'nullable',
            'bath' => 'nullable',
            // 'beds' => 'required_if:type,residential',
            // 'bath' => 'required_if:type,residential',
            'purpose' => 'nullable',
            'area' => 'nullable',
            'status' => 'required',
            // 'amenities' => 'required',
            // 'category_id' => 'required',
            // 'city_id' => 'required',
            'latitude' => ['required', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['required', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/']

        ];
        $basicSettings = BasicSetting::where('user_id', $userId)->select('property_state_status', 'property_country_status')->first();


        $languages = Language::where('user_id', $userId)->get();

        foreach ($languages as $language) {
            if ($basicSettings->property_country_status == 1) {
                $rules[$language->code . '_country_id'] = 'required';
            }

            $rules[$language->code . '_amenities'] = 'nullable';
            $rules[$language->code . '_category_id'] = 'required';
            $rules[$language->code . '_city_id'] = 'required';

            $rules[$language->code . '_title'] = 'required|max:255';
            $rules[$language->code . '_address'] = 'required';
            $rules[$language->code . '_description'] = 'required|min:15';

            $rules[$language->code . '_label'] = 'nullable|array';
            $rules[$language->code . '_value'] = 'nullable|array';
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

            $message[$language->code . '_amenities.required'] = 'The amenities field is required ' . $language->name . ' language.';
            $message[$language->code . '_category_id.required'] = 'The category field is required ' . $language->name . ' language.';
            $message[$language->code . '_city_id.required'] = 'The city field is required ' . $language->name . ' language.';
            $message[$language->code . '_country_id.required'] = 'The country field is required ' . $language->name . ' language.';
        }
        $message['beds.required_if'] = 'The beds field is required.';
        $message['bath.required_if'] = 'The bath field is required.';
        $message['featured_image.required'] = 'The thumbnail image field is required.';

        return $message;
    }
}

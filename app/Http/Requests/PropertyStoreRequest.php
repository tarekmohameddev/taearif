<?php

namespace App\Http\Requests;

use App\Models\User\BasicSetting;
use App\Models\User\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PropertyStoreRequest extends FormRequest
{
    private $userId;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (Auth::guard('web')->check()) {
            $this->userId = Auth::guard('web')->user()->id;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {

        $rules = [
            'slider_images' => 'required|array',
            'featured_image' => 'required|mimes:png,jpg,jpeg,svg,webp',
            'floor_planning_image' => 'nullable|mimes:png,jpg,jpeg,svg,webp',
            'price' => 'nullable|numeric',
            'beds' => 'nullable',
            'bath' => 'nullable',
            'purpose' => 'nullable',
            'area' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['required', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['required', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/']

        ];


        $basicSettings = BasicSetting::where('user_id', $this->userId)->select('property_state_status', 'property_country_status')->first();

        $languages = Language::where('user_id', $this->userId)->get();

        foreach ($languages as $language) {
            $rules[$language->code . '_category_id'] = 'required';
            $rules[$language->code . '_city_id'] = 'required';
            $rules[$language->code . '_amenities'] = 'nullable';
            $rules[$language->code . '_title'] = 'required|max:255';
            $rules[$language->code . '_address'] = 'required';
            $rules[$language->code . '_description'] = 'required|min:15';
            $rules[$language->code . '_label'] = 'nullable|array';
            $rules[$language->code . '_value'] = 'nullable|array';

            if ($basicSettings->property_country_status == 1) {
                $rules[$language->code . '_country_id'] =  'required';
            }
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

        $languages = Language::where('user_id', $this->userId)->get();

        foreach ($languages as $language) {
            $message[$language->code . '_category_id.required'] = 'حقل الفئات مطلوب ';
            $message[$language->code . '_city_id.required'] = 'المدينة مطلوبة';
            $message[$language->code . '_country_id.required'] = 'The country field is required ';
            $message[$language->code . '_amenities.required'] = 'حقل الفئات مطلوب ';
            $message[$language->code . '_latitude.required'] = 'حقل خط العرض مطلوب';
            $message[$language->code . '_longitude.required'] = 'حقل خط الطول مطلوب';
            $message[$language->code . '_title.required'] = 'العنوان مطلوب ';
            $message[$language->code . '_address.required'] = 'العنوان مطلوب ';
            $message[$language->code . '_description.required'] = 'حقل الوصف مطلوب';
            $message[$language->code . '_description.min'] = 'حقل الوصف يجب ان يكون على الاقل  :min حرف  ';
            $message[$language->code . '_label.max'] = 'عدد الحقول الاضافية يجب ان لا يتخطى ' . $language->name . ' :max.';
        }

        $message['beds.required_if'] = 'The beds field is required.';
        $message['bath.required_if'] = 'The bath field is required.';
        $message['slider_images.required'] = 'صور المعرض مطلوبة';

        $message['featured_image.required'] = 'صورة الغلاف مطلوبة';
        return $message;
    }
}

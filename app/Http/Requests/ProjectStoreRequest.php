<?php

namespace App\Http\Requests;

use App\Models\User\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProjectStoreRequest extends FormRequest
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
            'gallery_images' => 'required|array',
            'floor_plan_images' => 'required',
            'featured_image' => 'required|mimes:png,jpg,jpeg,svg',
            'min_price' => 'required|numeric',
            'max_price' => 'nullable|numeric',
            'featured' => 'sometimes',
            'status' => 'sometimes',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/']
        ];

        $languages = Language::where('user_id', $userId)->get();

        foreach ($languages as $language) {
            $rules[$language->code . '_title'] = 'required|max:255';
            $rules[$language->code . '_address'] = 'required';
            $rules[$language->code . '_description'] = 'required|min:15';
            $rules[$language->code . '_label'] = 'array';
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
        $languages = Language::get();

        foreach ($languages as $language) {

            $message[$language->code . '_title.required'] = 'حقل اسم المشروع مطلوب';
            $message[$language->code . '_address.required'] = 'حقل العنوان مطلوب';
            $message[$language->code . '_description.required'] = 'حقل الوصف مطلوب';
            $message[$language->code . '_description.min'] = 'الوصف يجب على الاقل ان يكون :min حرف  ';
            $message[$language->code . '_label.max'] = 'المميزات الاضافية يجيب ان لا تتعدى :max ';
        }
        $message['min_price.required'] = 'The minimum price field is required.';
        $message['max_price.numeric'] = 'The maximum price must be numeric.';
        $message['floor_plan_images.required'] = 'صورة المخطط مطلوبة';
        $message['gallery_images.required'] = 'صور المشروع مطلوبين';
        $message['featured_image.required'] = 'صورة الغلاف مطلوبة';
        $message['floor_plan_images.required'] = 'صورة المخطط مطلوبة';

        return $message;
    }
}

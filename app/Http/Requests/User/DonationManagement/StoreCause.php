<?php

namespace App\Http\Requests\User\DonationManagement;

use App\Models\User\DonationManagement\DonationContent;
use App\Models\User\Language;
use App\Rules\ImageMimeTypeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCause extends FormRequest
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
        $ruleArray = [
            'image' => [
                'required',
                new ImageMimeTypeRule()
            ],
            'goal_amount' => 'required',
            'min_amount' => 'required',


        ];

        $languages = Language::where('user_id', Auth::guard('web')->user()->id)->get();

        $request = $this->request->all();
        foreach ($languages as $language) {
            $slug = slug_create($request[$language->code . '_title']);
            $ruleArray[$language->code . '_title'] = [
                'required',
                'max:255',
                function ($attribute, $value, $fail) use ($slug, $language) {
                    $cis = DonationContent::where('language_id', $language->id)->where('user_id', Auth::guard('web')->user()->id)->get();
                    foreach ($cis as $key => $ci) {
                        if (strtolower($slug) == strtolower($ci->slug)) {
                            $fail('The title field must be unique for ' . $language->name . ' language.');
                        }
                    }
                }
            ];
            $ruleArray[$language->code . '_category_id'] = 'required';
            $ruleArray[$language->code . '_content'] = 'min:30';
        }

        return $ruleArray;
    }
    public function messages(): array
    {
        $messageArray = [];
        $languages = Language::where('user_id', Auth::guard('web')->user()->id)->get();

        foreach ($languages as $language) {
            $messageArray[$language->code . '_title.required'] = 'The title field is required for ' . $language->name . ' language.';
            $messageArray[$language->code . '_category_id.required'] = 'The category field is required for ' . $language->name . ' language.';
            $messageArray[$language->code . '_title.max'] = 'The title field cannot contain more than 255 characters for ' . $language->name . ' language.';

            $messageArray[$language->code . '_content.min'] = 'The description must be at least 30 characters for ' . $language->name . ' language.';
        }
        return $messageArray;
    }
}

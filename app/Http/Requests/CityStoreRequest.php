<?php

namespace App\Http\Requests;

use App\Models\User\BasicSetting;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CityStoreRequest extends FormRequest
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
        $country =  $this->country;
        $rules = [
            'state' => [
                Rule::requiredIf(function () use ($country, $userId) {
                    if ($country) {
                        $country = Country::where('user_id', $userId)->findOrFail($country);
                        if (count($country->states) > 0) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                    return false;
                })
            ],
            'image' => "required|mimes:jpg,png,svg,jpeg,webp",
            'status' => 'required|numeric',
            'language' => 'required',
            'serial_number' => 'required|numeric'
        ];

        $basicSettings = BasicSetting::where('user_id', $userId)->select('property_state_status', 'property_country_status')->first();
        if ($basicSettings->property_country_status == 1) {
            $rules['country'] = 'required';
        }
        // if ($basicSettings->property_state_status == 1) {
        //     $rules['state'] = 'required';
        // }

        $rules['name'] =
            [
                'required',
                Rule::unique('user_cities', 'name')->where('user_id', $userId)
            ];

        return $rules;
    }
}

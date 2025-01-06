<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Session;
use App\Models\Language;
use Config;
use App\Models\BasicSetting as BS;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }

        $bs = $currentLang->basic_setting;

        $ruleArray = [
            'first_name' => 'required',
            'last_name' => 'required',
            'company_name' => 'required',
            'username' => 'required',
            'password' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required',
            'country' => 'required',
            'price' => 'required',
            'payment_method' => $this->price != 0 ? 'required' : '',
            'receipt' => $this->is_receipt == 1 ? 'required | mimes:jpeg,jpg,png' : '',
            'cardNumber' => 'sometimes|required',
            'month' => 'sometimes|required',
            'year' => 'sometimes|required',
            'cardCVC' => 'sometimes|required',
            'identity_number' => $this->payment_method == 'Iyzico' ? 'required' : '',
            'zip_code' => $this->payment_method == 'Iyzico' ? 'required' : '',

        ];

        if ($bs->is_recaptcha == 1) {
            $ruleArray['g-recaptcha-response'] = 'required|captcha';
        }

        if ($this->payment_method == 'stripe') {
            $ruleArray['stripeToken'] = 'required';
        }
        return $ruleArray;
    }

    public function messages(): array
    {
        return [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
            'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
            'receipt.required' => 'The receipt field image is required when instruction required receipt image'
        ];
    }
}

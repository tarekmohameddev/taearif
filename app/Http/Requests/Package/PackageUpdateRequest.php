<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;

class PackageUpdateRequest extends FormRequest
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
        return [
            'title' => 'required|max:255',
            'icon' => 'required',
            'price' => 'required',
            'term' => 'required',
            'serial_number' => 'required|integer',
            'trial_days' => $this->is_trial == "1" ? 'required' : '',
            'video_size_limit' => is_array($this->features) && in_array('Course Management', $this->features) ? 'required|integer' : '',
            'file_size_limit' => is_array($this->features) && in_array('Course Management', $this->features) ? 'required|integer' : '',
            'number_of_vcards' => is_array($this->features) && in_array('vCard', $this->features) ? 'required|integer' : '',

        ];
    }
    public function messages(): array
    {
        return [
            'trial_days.required' => 'Trial days is required when trial option is checked',
            'video_size_limit.required' => 'Maximum Size of Single File is required when Course Management option is checked',
            'file_size_limit.required' => 'Maximum Size of Single Video is required when Course Management option is checked',
        ];
    }
}

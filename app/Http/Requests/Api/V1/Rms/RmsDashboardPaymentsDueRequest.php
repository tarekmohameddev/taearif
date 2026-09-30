<?php
namespace App\Http\Requests\Api\V1\Rms;
use Illuminate\Foundation\Http\FormRequest;
class RmsDashboardPaymentsDueRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['period'=>'sometimes|in:this_month,next_month,this_year','page'=>'sometimes|integer|min:1','per_page'=>'sometimes|integer|min:1|max:100']; } }

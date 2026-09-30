<?php
namespace App\Http\Requests\Api\V1\Rms;
use Illuminate\Foundation\Http\FormRequest;
class RmsDashboardPaginationRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['page'=>'sometimes|integer|min:1','per_page'=>'sometimes|integer|min:1|max:100']; } }

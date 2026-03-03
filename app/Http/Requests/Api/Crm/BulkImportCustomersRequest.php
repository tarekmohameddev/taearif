<?php

namespace App\Http\Requests\Api\Crm;

use App\Http\Requests\Api\BaseApiFormRequest;

class BulkImportCustomersRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'file' => 'required|mimes:xlsx,csv|max:10240',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'A file is required for import. Please select an Excel or CSV file.',
            'file.mimes' => 'The file must be an Excel (.xlsx) or CSV (.csv) file.',
            'file.max' => 'The file size must not exceed 10MB. Please use a smaller file or split it into multiple files.',
        ];
    }
}

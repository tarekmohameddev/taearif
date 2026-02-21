<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class AddNoteRequest extends BaseApiFormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'note' => 'required|string',
            'addedBy' => 'nullable|string|max:255',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveMapRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return
        [
            'map' => 'required|file|mimetypes:application/json,text/json',
            'schema_version' => 'required|integer|min:1'
        ];
    }
}


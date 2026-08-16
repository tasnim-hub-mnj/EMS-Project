<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RawJsonMapRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return
        [
            'canvasWidth' => 'required|integer',
            'canvasHeight' => 'required|integer',
            'backgroundColor' => 'required|string',
            'theme' => 'required|string',
            'unit' => 'required|string',
            'metersPerUnit' => 'required|numeric',
            'venue' => 'required|array',
            'floors' => 'required|array',
            'elements' => 'required|array'
        ];
    }
}


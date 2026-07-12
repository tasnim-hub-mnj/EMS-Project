<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBoothRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return
        [
            'number'   => 'sometimes|string|max:20',
            'area'     => 'sometimes|numeric',
            'status'   => 'sometimes|string|in:available,unavailable',
            'price'    => 'sometimes|numeric',
            'location' => 'sometimes|string|max:200',
            'services' => 'sometimes|json',
            'image'=> 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
            'map_x' => 'sometimes|numeric',
            'map_y' => 'sometimes|numeric',
            'map_z' => 'sometimes|numeric',
        ];
    }
}

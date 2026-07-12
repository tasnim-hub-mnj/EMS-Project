<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoothRequest extends FormRequest
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
            'number' => 'required|string|max:20',
            'area' => 'required|numeric',
            'status' => 'nullable|string|in:available,unavailable',
            'price' => 'required|numeric',
            'location' => 'required|string|max:200',
            'services' => 'nullable|array',
            'image'=> 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'map_x' => 'nullable|numeric',
            'map_y' => 'nullable|numeric',
            'map_z' => 'nullable|numeric',
        ];
    }
}

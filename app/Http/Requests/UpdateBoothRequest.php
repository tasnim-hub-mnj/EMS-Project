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
            'number'        => 'nullable|string|max:50',
            'section'       => 'nullable|string|max:50',
            'area'          => 'nullable|numeric|min:1',

            // حالة المنظّم فقط
            'status'        => 'nullable|in:available,unavailable',

            'pricing_type'  => 'nullable|in:total,daily',
            'price'         => 'nullable|numeric|min:0',

            'location'      => 'nullable|string|max:255',

            // خدمات إضافية
            'services'      => 'nullable|array',
            'services.*'    => 'numeric|min:0',

            // خدمات أساسية
            'amenities'     => 'nullable|array',
            'amenities.*'   => 'numeric|min:0',

            'description'   => 'nullable|string|max:500',

            'map_x'         => 'nullable|integer|min:0',
            'map_y'         => 'nullable|integer|min:0',
            'map_width'     => 'nullable|integer|min:0',
            'map_height'    => 'nullable|integer|min:0',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingshipSponsorEventRequest extends FormRequest
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
        [//BookingshipSponsorEventRequest
            'days' => 'required|integer|min:1',
            'description' => 'nullable|string|max:2000',

            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'product_images' => 'nullable|array',
            'product_images.*.name' => 'required|string|max:255',
            'product_images.*.image' => 'required|image|mimes:jpg,jpeg,png|max:4096',

            'materials' => 'nullable|array',
            'materials.*' => 'image|mimes:jpg,jpeg,png|max:4096',
        ];
    }
}

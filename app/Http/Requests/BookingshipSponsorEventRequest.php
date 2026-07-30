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
        [
            'event_id' => 'required|integer|exists:sponsor_events,id',

            'selected_duration_label' => 'nullable|string|max:255',
            'selected_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',

            'company_name' => 'required|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_phone' => 'nullable|string|max:20',
            'product_names' => 'nullable|string|max:2000',

            // logo
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',

            // ad images
            'ad_images' => 'nullable|array',
            'ad_images.*' => 'image|mimes:jpg,jpeg,png|max:4096',

            // poster images
            'poster_images' => 'nullable|array',
            'poster_images.*' => 'image|mimes:jpg,jpeg,png|max:4096',

            // product images
            'product_images' => 'nullable|array',
            'product_images.*.name' => 'required|string|max:255',
            'product_images.*.image' => 'required|image|mimes:jpg,jpeg,png|max:4096',
        ];
    }
}

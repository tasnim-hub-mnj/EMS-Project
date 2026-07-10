<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestorProfileRequest extends FormRequest
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
            'bio' =>     'nullable|string|max:500',
            'logo'=>     'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'links' => 'nullable|array',
            'links.*.link' => 'required|string|max:300',
            'links.*.type' => 'required|string|max:50',
            'links.*.id' => 'nullable|integer|exists:social_links,id',

            'location'       => 'sometimes|string|max:200',
            'website'        => 'sometimes|url',
            'email'          => 'sometimes|email|unique:users,email',
            'phone'          => 'sometimes|string|unique:users,phone',
        ];
    }
}

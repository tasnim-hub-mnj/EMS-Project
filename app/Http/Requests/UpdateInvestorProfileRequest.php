<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        $userId = Auth::id();

        return
        [
            // USER fields
            'email' =>
            [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],

            'phone' =>
            [
                'required',
                'string',
                Rule::unique('users', 'phone')->ignore($userId)
            ],

            // INVESTOR fields
            'company_name' => 'required|string|max:255',
            'location'     => 'required|string|max:255',
            'website'      => 'nullable|string|max:255',
            'bio'          => 'nullable|string|max:2000',

            // SOCIAL object
            'social' => 'nullable|array',

            'social.linkedin'  => 'nullable|string|max:255',
            'social.twitter'   => 'nullable|string|max:255',
            'social.instagram' => 'nullable|string|max:255',
            'social.facebook'  => 'nullable|string|max:255',
        ];
    }
}

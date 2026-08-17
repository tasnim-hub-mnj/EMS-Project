<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrganizerRegisterRequest extends FormRequest
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
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'required|string|unique:users,phone',
            'password'       => 'required|string|min:6|confirmed',

            'company_name'   => 'required|string|max:200',
            'category'     => 'required|array',
            'headquarters'       => 'required|string|max:200',
            'registration_number'        => 'required|string|max:200',
            'exhibition_location'        => 'required|string|max:200',
            'description'      => 'nullable|string|max:500',
            // 'logo'      =>  'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // 'file'        => 'required|file',

            // 'fcm_token'      => 'nullable|string|max:400',
        ];
    }
}

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
            'password'       => 'required|string|min:6|confirmed',
            'phone'          => 'required|string|unique:users,phone',
            'company_name'   => 'required|string|max:200',
            'category'       => 'required|string|max:200',
            'headquarters'   => 'required|string|max:200',
            'registration_number' => ['required', 'string', 'max:200', 'unique:organizers,reg_number'],
            'exhibition_location' => 'required|string|max:200',
            'description'    => 'nullable|string|max:500',
            'logo'           => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'legalDocument'  => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:12288',
            'fcm_token'      => 'nullable|string|max:400',
        ];
    }
}

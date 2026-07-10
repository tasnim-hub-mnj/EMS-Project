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
        [//OrganizerRegisterRequest
            'company_name'   => 'required|string|max:200',
            'category'     => 'required|json',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'required|string|unique:users,phone',
            'password'       => 'required|string|min:6|confirmed',
            'token_fcm'      => 'required|string|max:400',
            'headquarters'       => 'required|string|max:200',
            'reg_number'        => 'required|string|max:200',
            'location'        => 'required|string|max:200',
            'logo'      =>  'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'file'        => 'required|file',
            'description'      => 'required|string|max:500',
        ];
    }
}

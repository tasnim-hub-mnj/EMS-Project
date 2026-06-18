<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return
        [
            'email' => 'nullable|email|unique:users,email|required_without:phone|prohibited_with:phone',
            'phone' => 'nullable|unique:users,phone|required_without:email|prohibited_with:email',
            'role' => 'required|in:manager,investor,visitor,staff',
            'password' => 'required|string|min:6|confirmed',
        ];
    }
}

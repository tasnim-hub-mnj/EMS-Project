<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvestorRedisterRequest extends FormRequest
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
            'company_name'   => 'required|string|max:200',
            'trade_name'     => 'nullable|string|max:200',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'required|string|unique:users,phone',
            'website'        => 'nullable|url',
            'activity_type'  => 'required|string|max:200',
            'password'       => 'required|string|min:6|confirmed',
            'terms_accepted' => 'required|boolean|in:1',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvestorRegisterRequest extends FormRequest
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
            'trade_name'     => 'required|string|max:200',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'required|string|unique:users,phone',
            'token_fcm'      => 'required|string|max:400',
            'location'       => 'required|string|max:200',
            'website'        => 'nullable|url',
            'activity_type' => 'required|in:technology,food&hospitality,fashion,health,education,other',
            'password'       => 'required|string|min:6|confirmed',
            'terms_accepted' => 'required|boolean|in:1',

            // 'bio' =>     'nullable|string|max:500',
            // 'logo'=>     'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // 'link'=>     'nullable|string|max:300',
            // 'type'=>     'nullable|string|max:50',
        ];
    }
}

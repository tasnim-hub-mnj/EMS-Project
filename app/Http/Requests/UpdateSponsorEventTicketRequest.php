<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSponsorEventTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {//o
        return
        [
            'type' => 'sometimes|string',
            'holder_name' => 'sometimes|string|max:255',
            'holder_email' => 'sometimes|email',
            'holder_phone' => 'sometimes|string|max:20',

            'delivery_method' => 'sometimes|in:email,sms,manual',

            'paid_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pending,confirmed,attended,cancelled',
        ];
    }
}

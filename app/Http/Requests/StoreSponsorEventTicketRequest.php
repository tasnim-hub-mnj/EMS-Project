<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSponsorEventTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {//o
        return
        [
            'event_id' => 'required|exists:sponsor_events,id',

            'type' => 'required|string', // invitation / paid

            'holder_name' => 'required|string|max:255',
            'holder_email' => 'required|email',
            'holder_phone' => 'nullable|string|max:20',

            'delivery_method' => 'required|in:email,sms,manual',

            'paid_amount' => 'nullable|numeric|min:0',
        ];
    }
}

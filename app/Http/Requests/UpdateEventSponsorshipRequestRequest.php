<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventSponsorshipRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'sometimes|string|max:255',
            'company_type' => 'sometimes|string|max:255',

            'contact_name' => 'sometimes|string|max:255',
            'contact_phone' => 'sometimes|string|max:50',
            'contact_email' => 'sometimes|email',

            'proposed_amount' => 'sometimes|numeric|min:0',
            'offer_details' => 'sometimes|string',
            'conditions' => 'sometimes|string',

            'organizer_notes' => 'sometimes|string',
            'reject_reason' => 'sometimes|string',

            'status' => 'sometimes|in:new,pending,negotiating,approved,rejected',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSponsorshipRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return
        [
            'company_name' => 'sometimes|string|max:255',
            'company_type' => 'sometimes|string|max:255',
            'website' => 'sometimes|string|max:255',

            'contact_name' => 'sometimes|string|max:255',
            'contact_phone' => 'sometimes|string|max:50',
            'contact_email' => 'sometimes|email',

            'proposed_tier' => 'sometimes|in:title,gold,silver,bronze',
            'proposed_amount' => 'sometimes|numeric|min:0',

            'offer_details' => 'sometimes|string',
            'conditions' => 'sometimes|string',
            'contract_terms' => 'sometimes|string',

            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',

            'status' => 'sometimes|in:new,pending,negotiating,approved,rejected',
            'reject_reason' => 'sometimes|string',
            'organizer_notes' => 'sometimes|string',
        ];
    }
}

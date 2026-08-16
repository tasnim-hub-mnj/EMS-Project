<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSponsorshipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return
        [
            'exhibition_id' => 'required|exists:exhibitions,id',

            'company_name' => 'required|string|max:255',
            'company_type' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',

            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email',

            'proposed_tier' => 'nullable|in:title,gold,silver,bronze',
            'proposed_amount' => 'nullable|numeric|min:0',

            'offer_details' => 'nullable|string',
            'conditions' => 'nullable|string',
            'contract_terms' => 'nullable|string',

            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventSponsorshipRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {//o
        return
        [
            'sponsor_event_id' => 'required|exists:sponsor_events,id',
            'exhibition_id' => 'required|exists:exhibitions,id',

            'company_name' => 'required|string|max:255',
            'company_type' => 'nullable|string|max:255',

            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email',

            'proposed_amount' => 'nullable|numeric|min:0',
            'offer_details' => 'nullable|string',
            'conditions' => 'nullable|string',

            'organizer_notes' => 'nullable|string',
            'reject_reason' => 'nullable|string',

            'status' => 'nullable|in:new,pending,negotiating,approved,rejected',
        ];
    }
}

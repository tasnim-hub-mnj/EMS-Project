<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return
        [
            'name' => 'sometimes|string|max:255',
            'logo' => 'sometimes|image',
            'tier' => 'sometimes|in:title,gold,silver,bronze',
            'website' => 'sometimes|string|max:255',
            'contact_name' => 'sometimes|string|max:255',
            'contact_phone' => 'sometimes|string|max:50',
            'contact_email' => 'sometimes|email',
            'amount' => 'sometimes|numeric|min:0',
            'notes' => 'sometimes|string',
            'status' => 'sometimes|in:active,pending,cancelled',
        ];
    }
}

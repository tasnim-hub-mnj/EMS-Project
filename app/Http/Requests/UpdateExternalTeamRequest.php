<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExternalTeamRequest extends FormRequest
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
            'company' => 'sometimes|string|max:255',
            'role' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'offical_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|string|max:50',
            'amount' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'classification' => 'sometimes|string|max:255',
            'notes' => 'sometimes|string',
            'status' => 'sometimes|in:pending,active,finished',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExternalTeamRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $aliases = [
            'amount' => 'contract_value',
            'classification' => 'category',
            'offical_name' => 'contact_name',
            'email' => 'contact_email',
            'phone' => 'contact_phone',
        ];
        $normalized = [];

        foreach ($aliases as $canonical => $alias) {
            if ($this->has($canonical)) {
                $normalized[$canonical] = $this->input($canonical);
            } elseif ($this->has($alias)) {
                $normalized[$canonical] = $this->input($alias);
            }
        }

        $this->merge($normalized);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return
        [
            'name' => 'sometimes|string|max:255',
            'company' => 'sometimes|nullable|string|max:255',
            'role' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'offical_name' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email',
            'phone' => 'sometimes|nullable|string|max:50',
            'amount' => 'sometimes|nullable|numeric|min:0',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date',
            'classification' => 'sometimes|nullable|string|max:255',
            'notes' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:pending,active,finished,rejected',
        ];
    }
}

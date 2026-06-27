<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingVisitorRequest extends FormRequest
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
        return [

            'type' => 'required|in:exhibition,event',
            'exhibition_id' => 'nullable|required_if:type,exhibition|exists:exhibitions,id',
            'event_id' => 'nullable|required_if:type,event|exists:events,id',
            'amount' => 'nullable|numeric',
        ];

    }
}

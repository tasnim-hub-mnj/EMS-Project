<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSponsorEventRequest extends FormRequest
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
        return
        [//StoreSponsorEventRequest

            'name' => 'required|string|max:200',
            'type' => 'required|string|max:200',
            'max_participants' => 'required|integer',
            'description' => 'nullable|string|max:200',
            'start_time' => 'required|date_format:Y-m-d h:i A',
            'end_time'   => 'required|date_format:Y-m-d h:i A|after:start_time',
            'place' => 'required|string|max:300',
            'is_general_invitation' => 'nullable|boolean',
            'ticket_price' => 'nullable|numeric',
            'copy_status' => 'nullable|in:draft,active,archived',

        ];
    }
}

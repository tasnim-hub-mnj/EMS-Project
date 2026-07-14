<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BookingBoothRequest extends FormRequest
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
        [//BookingBoothRequest
            'start_date' =>
            [
                'required',
                'date',
                Rule::afterOrEqual($this->exhibition_start),
                Rule::beforeOrEqual($this->exhibition_end),
            ],

            'duration_days' => 'required|integer|min:1',
            'additional_services' => 'nullable|json',
            'notes' => 'nullable|string',
            'services_products'=>'nullable|string|max:1000',

            'image_b' => 'nullable|array',
            'image_b.*' => 'image|mimes:jpg,jpeg,png|max:2048',

            'image_p' => 'nullable|array',
            'image_p.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator)
        {

            if (!$this->start_date || !$this->duration_days)
            {
                return;
            }

            // حساب تاريخ نهاية الحجز
            $start = Carbon::parse($this->start_date);
            $end   = $start->copy()->addDays($this->duration_days - 1);

            // نهاية المعرض
            $exhibitionEnd = Carbon::parse($this->exhibition_end);

            // ❗ شرط: لا يتجاوز نهاية المعرض
            if ($end->gt($exhibitionEnd))
            {
                $validator->errors()->add(
                    'duration_days',
                    'The booking end date exceeds the exhibition end date.'
                );
            }

            // ❗ شرط: عدد الأيام المتاحة بناءً على تاريخ البداية
            // مثال: لو بدأ في اليوم الثالث من المعرض ومدته 5 أيام → المتاح فقط 3 أيام
            $maxDays = $start->diffInDays($exhibitionEnd) + 1;

            if ($this->duration_days > $maxDays)
            {
                $validator->errors()->add(
                    'duration_days',
                    "You can only book up to $maxDays days starting from this date."
                );
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
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
            // المعلومات الأساسية
            'name'  => 'required|string|max:255',
            'type'  => 'nullable|string|max:255',

            // الجناح (المعرض نستخرجه من الجناح)
            'booth_id' => 'required|exists:booths,id',

            // التاريخ والوقت
            'date' => 'required|date',
            'time' => 'required',

            // مدة الفعالية
            'duration_days' => 'required|integer|min:1|max:30',

            // الوصف
            'description' => 'nullable|string',

            // الصور
            'company_images'   => 'nullable|array',
            'company_images.*' => 'image|mimes:jpg,jpeg,png|max:4096',

            // الفيديو الترويجي
            'video_promo_url' => 'nullable|url',

            // التسجيل والتذاكر
            'has_bookable_seats' => 'required|boolean',
            'total_seats'        => 'nullable|integer|min:1',

            'max_participants' => 'nullable|integer|min:1',

            // الدعوة العامة
            'is_general_invitation' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'اسم الفعالية مطلوب',
            'booth_id.required' => 'يجب اختيار الجناح',
            'date.required' => 'تاريخ الفعالية مطلوب',
            'time.required' => 'وقت الفعالية مطلوب',
            'duration_days.required' => 'مدة الفعالية مطلوبة',
            'has_bookable_seats.required' => 'يجب تحديد نوع المقاعد',
            'is_general_invitation.required' => 'يجب تحديد نوع الدعوة',
        ];
    }
}

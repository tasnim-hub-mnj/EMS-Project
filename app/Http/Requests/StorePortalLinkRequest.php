<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePortalLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return
        [

            // الخطوة 3: بيانات الموظف
            'staff_id' => 'required|exists:staff_members,id',
            'staff_name' => 'required|string|max:255',
            'staff_email' => 'nullable|email|max:255',
            'staff_title' => 'nullable|string|max:255',
            'staff_number' => 'required|string|max:50',

            // معلومات المعرض
            'exhibition_id' => 'required|exists:exhibitions,id',
            'exhibition_name' => 'required|string|max:255',

            // الخطوة 1: الدور
            'role' => 'required|string|in:administrative,organizational,services,external',

            // الخطوة 2: الصلاحيات المختارة
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|max:255',

            // قنوات الرسائل (اختياري)
            'messaging_channels' => 'nullable|array',
            'messaging_channels.*' => 'string|max:255',

            // هل هو مدير؟
            'is_manager' => 'required|boolean',

            // من أنشأ الرابط
            'created_by' => 'nullable|string|max:255',
            'created_by_name' => 'nullable|string|max:255',

            // رابط فرعي
            'parent_token' => 'nullable|uuid',
        ];
    }
}

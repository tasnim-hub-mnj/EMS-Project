<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        $fieldAliases = [
            'national_id' => 'nationalId',
            'attendance_rate' => 'attendanceRate',
            'tasks_completed' => 'tasksCompleted',
            'tasks_total' => 'tasksTotal',
            'payment_period' => 'paymentPeriod',
            'work_days' => 'workDays',
            'id_image' => 'idImage',
            'profile_image' => 'profileImage',
            'cv_file' => 'cvFile',
            'contract_file' => 'contractFile',
            'qr_code' => 'qrCode',
        ];

        foreach ($fieldAliases as $snake => $camel) {
            if (isset($data[$snake]) && !isset($data[$camel])) {
                $data[$camel] = $data[$snake];
            }
        }

        foreach (['workDays', 'work_days'] as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $data[$field] = array_values($decoded);
                } elseif (trim($value) !== '') {
                    $parts = array_map('trim', preg_split('/[,\[\]]+/', $value, -1, PREG_SPLIT_NO_EMPTY));
                    $data[$field] = array_values(array_filter($parts, fn ($part) => $part !== ''));
                }
            }
        }

        if (isset($data['team']) && is_string($data['team'])) {
            $team = strtolower(trim($data['team']));
            $normalizedTeam = match ($team) {
                'service' => 'services',
                'technical' => 'organizational',
                default => $team,
            };
            $data['team'] = $normalizedTeam;
        }

        if (isset($data['paymentPeriod']) && is_string($data['paymentPeriod'])) {
            $paymentPeriod = strtolower(trim($data['paymentPeriod']));
            $data['paymentPeriod'] = $paymentPeriod === 'biweekly' ? 'bi-weekly' : $paymentPeriod;
        }

        $this->replace($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return
        [
            'email' => 'required|email',
            'phone' => 'required|string|max:50',

            'name' => 'required|string|max:255',
            'type' => 'nullable|in:permanent,temporary',
            'role' => 'nullable|string|max:255',
            'rank' => 'nullable|string|max:255',
            'team' => 'nullable|in:administrative,organizational,services,service',

            'nationalId' => 'nullable|string|max:20',

            'schedule' => ['nullable', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d\s-\s(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'attendanceRate' => 'nullable|numeric|min:0|max:100',
            'tasksCompleted' => 'nullable|integer|min:0',
            'tasksTotal' => 'nullable|integer|min:0',

            'salary' => 'nullable|numeric|min:0',
            'paymentPeriod' => 'nullable|in:monthly,bi-weekly,biweekly,weekly,daily,hourly',
            'qrCode' => 'nullable|string|max:255',

            'workDays' => 'nullable|array',

            'idImage' => 'nullable|image',
            'profileImage' => 'nullable|image',
            'cvFile' => 'nullable|file|mimes:pdf',
            'contractFile' => 'nullable|file|mimes:pdf',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $schedule = $this->input('schedule');
            if (!is_string($schedule) || !preg_match('/^(\d{2}):(\d{2})\s-\s(\d{2}):(\d{2})$/', $schedule, $matches)) {
                return;
            }

            $start = ((int) $matches[1] * 60) + (int) $matches[2];
            $end = ((int) $matches[3] * 60) + (int) $matches[4];
            if ($end <= $start) {
                $validator->errors()->add('schedule', 'وقت الانتهاء يجب أن يكون بعد وقت البدء.');
            }
        });
    }
}

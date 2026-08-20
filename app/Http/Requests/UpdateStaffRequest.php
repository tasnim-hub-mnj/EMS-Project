<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class UpdateStaffRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return
        [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:permanent,temporary',
            'role' => 'sometimes|string|max:255',
            'rank' => 'sometimes|string|max:255',
            'team' => 'sometimes|in:administrative,organizational,services,service',

            'nationalId' => 'sometimes|string|max:20',

            'schedule' => ['sometimes', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d\s-\s(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'attendanceRate' => 'sometimes|numeric|min:0|max:100',
            'tasksCompleted' => 'sometimes|integer|min:0',
            'tasksTotal' => 'sometimes|integer|min:0',

            'salary' => 'sometimes|numeric|min:0',
            'paymentPeriod' => 'sometimes|in:monthly,bi-weekly,biweekly,weekly,daily,hourly',
            'qrCode' => 'sometimes|string|max:255',

            'workDays' => 'sometimes|array',

            'idImage' => 'sometimes|image',
            'profileImage' => 'sometimes|image',
            'cvFile' => 'sometimes|file|mimes:pdf',
            'contractFile' => 'sometimes|file|mimes:pdf',
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

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return
        [
            'id' => $this->number,
            'name' => $this->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,

            'type' => $this->type,
            'role' => $this->role,
            'rank' => $this->rank,
            'team' => $this->team,

            'qrCode' => $this->qr_code,

            'schedule' => $this->schedule,
            'attendanceRate' => $this->attendanceRate,
            'tasksCompleted' => $this->tasksCompleted,
            'tasksTotal' => $this->tasksTotal,

            'nationalId' => $this->nationalId,

            'idImage' => $this->idImage ? asset('storage/' . $this->idImage) : null,
            'profileImage' => $this->profileImage ? asset('storage/' . $this->profileImage) : null,

            'cvFile' => $this->cvFile ? asset('storage/' . $this->cvFile) : null,
            'cvFileName' => $this->cvFileName,

            'salary' => $this->salary,
            'paymentPeriod' => $this->paymentPeriod,
            'workDays' => $this->workDays,

            'contractFile' => $this->contractFile ? asset('storage/' . $this->contractFile) : null,
            'contractFileName' => $this->contractFileName,

            'createdAt' => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
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
            'id' => 'a-' . $this->id,
            'staffId' => $this->staff->number,
            'staffName' => $this->staff->name,
            'team' => $this->staff->team,
            'checkIn' => $this->check_in,
            'checkOut' => $this->check_out,
            'hoursWorked' => $this->hours_worked,
            'date' => $this->date,
            'method' => strtolower($this->method), // QR → qr
        ];
    }
}

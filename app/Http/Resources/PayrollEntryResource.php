<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        return
        [
            'id' => 'p-' . $this->id,
            'staffId' => $this->staff->number,
            'staffName' => $this->staff->name,
            'team' => $this->type_staff,
            'month' => $this->year . '-' . $this->month,
            'gross' => $this->gross,
            'deductions' => $this->deductions,
            'net' => $this->net,
            'notes' => $this->notes,
        ];
    }
}

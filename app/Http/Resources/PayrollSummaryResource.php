<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollSummaryResource extends JsonResource
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
            'team' => $this['team'],
            'month' => $this['month'],
            'totalGross' => $this['totalGross'],
            'totalDeductions' => $this['totalDeductions'],
            'totalNet' => $this['totalNet'],
            'entries' => PayrollEntryResource::collection($this['entries']),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorSummaryResource extends JsonResource
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
            'investorId' => 'inv' . $this->id,
            'investorCompany' => $this->company_name,
            'contactEmail' => $this->user->email,
            'contactPhone' => $this->user->phone,

            'booths' => $this->boothBookings->map(function ($booking)
            {
                return
                [
                    'number' => $booking->booth->number,
                    'area' => $booking->booth->area,
                    'status' => $booking->status ?? 'approved',
                ];
            }),

            'totalAmount' => $this->total_value,
            'paidAmount' => $this->paid_amount,
        ];
    }
}

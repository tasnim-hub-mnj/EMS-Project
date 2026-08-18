<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booth = $this->booth;
        $investor = $this->investor;
        $copy = $this->copy;

        return 
        [
            'id' => 'bk' . $this->id,
            'exhibitionId' => (string) $booth->exhibition_id,
            'editionId' => $copy ? ($copy->id . '-ed-' . $copy->year) : null,

            'boothId' => 'b' . $booth->id,
            'boothNumber' => $booth->number,
            'boothArea' => $booth->area,
            'boothBasePrice' => $booth->price,

            'investorId' => 'inv' . $investor->id,
            'investorName' => $investor->user->name,
            'investorCompany' => $investor->company_name,
            'contactEmail' => $investor->user->email,
            'contactPhone' => $investor->phone,

            'status' => $this->status,
            'totalAmount' => $this->total_price,
            'paidAmount' => $this->paid_amount,

            'requestedAt' => $this->booked_at,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,

            'services' => $this->additional_services ?? [],
            'servicePrices' => $this->services_products ? json_decode($this->services_products, true) : [],

            'notes' => $this->notes,
            'rejectReason' => $this->status === 'rejected' ? $this->notes : null,
        ];
    }
}

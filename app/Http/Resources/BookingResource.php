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
        $servicePrices = $this->services_products;

        if (is_string($servicePrices)) {
            $servicePrices = json_decode($servicePrices, true);
        }

        if (!is_array($servicePrices)) {
            $servicePrices = [];
        }

        $services = $this->additional_services;
        if (is_string($services)) {
            $services = json_decode($services, true);
        }

        if (!is_array($services)) {
            $services = [];
        } elseif (!array_is_list($services)) {
            $services = array_keys($services);
        }

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

            'services' => $services,
            'servicePrices' => $servicePrices,

            'notes' => $this->notes,
            'rejectReason' => $this->status === 'rejected' ? $this->notes : null,
        ];
    }
}

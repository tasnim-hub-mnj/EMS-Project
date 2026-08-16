<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CopyResource extends JsonResource
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
            'id' => $this->id . '-ed-' . $this->year,
            'exhibitionId' => $this->exhibition_id,
            'label' => "نسخة " . $this->year,

            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'announced' => $this->announced,

            'totalBooths' => $this->total_booths,
            'bookedBooths' => $this->booked_booths,
            'availableBooths' => $this->available_booths,
            'pendingRequests' => $this->pending_requests,

            'visitorCount' => $this->visitor_count,
            'expectedVisitors' => $this->expected_visitors,

            'turnoutPercent' => $this->turnout_percent,
            'expectedTurnoutPercent' => $this->expected_turnout_percent,

            'revenue' => $this->revenue,
            'expectedRevenue' => $this->expected_revenue,

            'staffCount' => $this->staff_count,
            'sponsorshipPercent' => $this->sponsorship_percent,

            'finalBookedBooths' => $this->final_booked_booths,
        ];
    }
}

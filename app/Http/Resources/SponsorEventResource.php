<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorEventResource extends JsonResource
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
            'id' => 'ev-' . $this->id,
            'exhibitionId' => (string) $this->exhibition_id,
            'exhibitionName' => $this->exhibition?->name,
            'exhibition_name' => $this->exhibition?->name,
            'exhibition' => $this->exhibition ? [
                'id' => $this->exhibition->id,
                'name' => $this->exhibition->name,
            ] : null,

            'title' => $this->name,
            'description' => $this->description,
            'type' => $this->type,

            'startAt' => $this->start_time,
            'endAt' => $this->end_time,

            'venueName' => $this->place,
            'capacity' => $this->max_participants,

            'registered' => $this->registered_count,
            'attended' => $this->scanned_count,

            // organizer status
            'status' => $this->copy_status,

            'ticketType' => $this->ticket_type,
            'ticketPrice' => $this->ticket_price,
            'durationDays' => $this->duration_days,
            'dailyPrice' => $this->daily_price,
            'sponsorshipOptions' => $this->duration_options ?? [],

            'activities' => SponsorEventProgramResource::collection($this->programs),
            'photos' => SponsorEventImageResource::collection($this->sponsorEventImages),

            'publishedAt' => $this->publish_date,

            // optional future fields
            'sponsorshipRequests' => $this->sponsorshipBookings()->count(),
            'adContracts' => 0,
        ];
    }
}

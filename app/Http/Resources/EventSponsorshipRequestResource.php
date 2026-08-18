<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventSponsorshipRequestResource extends JsonResource
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
            'id' => 'evreq-' . $this->id,
            'exhibitionId' => (string) $this->exhibition_id,

            'eventName' => $this->sponsorEvent->name,
            'eventDate' => $this->sponsorEvent->start_time,

            'companyName' => $this->company_name,
            'companyType' => $this->company_type,

            'contactName' => $this->contact_name,
            'contactPhone' => $this->contact_phone,
            'contactEmail' => $this->contact_email,

            'proposedAmount' => $this->proposed_amount,
            'offerDetails' => $this->offer_details,
            'conditions' => $this->conditions,

            'requestDate' => $this->request_date,
            'status' => $this->status,
            'rejectReason' => $this->reject_reason,
            'organizerNotes' => $this->organizer_notes,
        ];
    }
}

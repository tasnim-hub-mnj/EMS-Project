<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorshipRequestResource extends JsonResource
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
            'id' => 'req-' . $this->id,
            'exhibitionId' => (string) $this->exhibition_id,

            'companyName' => $this->company_name,
            'companyType' => $this->company_type,
            'website' => $this->website,

            'contactName' => $this->contact_name,
            'contactPhone' => $this->contact_phone,
            'contactEmail' => $this->contact_email,

            'proposedTier' => $this->proposed_tier,
            'proposedAmount' => $this->proposed_amount,

            'offerDetails' => $this->offer_details,
            'conditions' => $this->conditions,
            'contractTerms' => $this->contract_terms,

            'startDate' => $this->start_date,
            'endDate' => $this->end_date,

            'requestDate' => $this->request_date,
            'status' => $this->status,
            'rejectReason' => $this->reject_reason,
            'organizerNotes' => $this->organizer_notes,

            'pastSponsorships' => $this->last_sponsor ?
            [
                [
                    'exhibitionName' => 'معرض التقنية والابتكار',
                    'year' => '2025',
                    'tier' => 'silver',
                    'amount' => 100000
                ]
            ] : [],
        ];
    }
}

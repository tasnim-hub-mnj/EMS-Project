<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorResource extends JsonResource
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
            'id' => 'sp-' . $this->id,
            'exhibitionId' => (string) $this->exhibition_id,
            'name' => $this->name,
            'logo' => $this->logo ? asset('storage/' . $this->logo) : null,
            'tier' => $this->tier,
            'website' => $this->website,
            'contactName' => $this->contact_name,
            'contactPhone' => $this->contact_phone,
            'contactEmail' => $this->contact_email,
            'amount' => $this->amount,
            'status' => $this->status,
            'notes' => $this->notes,
            'createdAt' => $this->created_at,
        ];
    }
}

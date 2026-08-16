<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorEventTicketResource extends JsonResource
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
            'id' => 't' . $this->id,
            'eventId' => 'ev-' . $this->sponsor_event_id,

            'type' => $this->type,
            'holderName' => $this->holder_name,
            'holderEmail' => $this->holder_email,
            'holderPhone' => $this->holder_phone,

            'status' => $this->status,
            'qrCode' => $this->qr_code,

            'deliveryMethod' => $this->delivery_method,
            'paidAmount' => $this->amount ?? 0,

            'createdAt' => $this->created_at,
            'attendedAt' => $this->attended_at,
        ];
    }
}

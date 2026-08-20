<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoothResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestBooking = $this->boothBookings && $this->boothBookings->isNotEmpty()
            ? $this->boothBookings->sortByDesc('created_at')->first()
            : null;

        return

        [
            'id' => 'b' . $this->id,
            'exhibitionId' => (string) $this->exhibition_id,
            'sectionId' => $this->section_id ? (string) $this->section_id : null,

            'number' => $this->number,
            'section' => $this->section,
            'area' => $this->area,

            // حالة البوث بالنسبة للمنظّم
            'status' => $this->status,

            // // حالة البوث بالنسبة للمستثمر
            // 'statusInv' => $this->status_inv,

            'price' => $this->price,
            'pricingType' => $this->pricing_type,
            'dailyPrice' => $this->pricing_type === 'daily' ? $this->price : null,

            // الخدمات الإضافية (services)
            'services' => $this->services ? collect($this->services)->map(function ($price, $name)
            {
                return
                [
                    'name' => $name,
                    'price' => $price
                ];
            })->values() : [],

            // الخدمات الأساسية (amenities)
            'amenities' => $this->amenities ? collect($this->amenities)->map(function ($price, $name)
            {
                return
                [
                    'name' => $name,
                    'price' => $price
                ];
            })->values() : [],

            // الصور
            'images' => $this->boothImages->pluck('image')->map(fn($img) => asset('storage/' . $img)),

            'description' => $this->description,

            // خريطة المعرض
            'mapX' => $this->map_x,
            'mapY' => $this->map_y,
            'mapWidth' => $this->map_width,
            'mapHeight' => $this->map_height,

            // بيانات المستثمر (إن وجد حجز Approved)
            'investorId' => $latestBooking?->investor_id,
            'investorName' => $latestBooking?->investor?->company_name,

            // بيانات الحجز (إن وجد)
            'booking' => $latestBooking ?
            [
                'companyName' => $latestBooking->investor?->company_name,
                'contactName' => $latestBooking->contact_name,
                'contactEmail' => $latestBooking->contact_email,
                'contactPhone' => $latestBooking->contact_phone,
                'bookedDays' => $latestBooking->days,
                'events' => [] // ما في فعاليات داخل البوث الآن
            ] : null
        ];
    }
}

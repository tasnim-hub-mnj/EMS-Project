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
        $booking = $this->boothBookings
            ->where('status', 'approved')
            ->filter(function ($b) {
                return $b->start_date <= now() && $b->end_date >= now();
            })
            ->first();
        

        return 
        [
            'id' => 'b' . $this->id,
            'exhibitionId' => (string) $this->exhibition_id,

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
            // بيانات المستثمر للحجز الفعّال
            'investorId' => optional($booking)->investor_id,
            'investorName' => optional(optional($booking)->investor)->company_name,

            // بيانات الحجز الفعّال
            'booking' => $booking ? 
            [
                'companyName' => optional($booking->investor)->company_name,
                // 'contactName' => $booking->contact_name,
                'contactEmail' => optional($booking->investor->user)->email,
                'contactPhone' => optional($booking->investor->user)->phone,
                'bookedDays' => $booking->days,
                'events' => []
            ] : null
        ];
    }
}

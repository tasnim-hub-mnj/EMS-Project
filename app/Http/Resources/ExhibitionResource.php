<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExhibitionResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'workingHours' => $this->working_hours,
            'status' => $this->status,
            'type' => $this->type,

            'totalBooths' => $this->total_booths,
            'bookedBooths' => $this->total_booths - $this->available_booths,
            'mapBuilt' => $this->map_built,
            'mapTheme' => $this->getMapTheme(),

            // النسخة الحالية
            'currentEditionId' => $this->copies()
                ->where('copy_status', 'active')
                ->first()
                ? $this->copies()->where('copy_status', 'active')->first()->id . '-ed-' . $this->copies()->where('copy_status', 'active')->first()->year
                : null,

            // كل النسخ
            'editions' => CopyResource::collection($this->copies),

            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
    //________________________________________
    private function getMapTheme()
    {
        // إذا ما في خريطة
        if (!$this->latestMap || !$this->latestMap->map_json) 
        {
            return null;
        }

        $path = $this->latestMap->map_json;

        // إذا الملف مش موجود
        if (!Storage::disk('public')->exists($path)) 
        {
            return null;
        }

        // قراءة الملف
        $json = json_decode(Storage::disk('public')->get($path), true);

        // theme من داخل JSON
        return $json['theme'] ?? null;
    }
}

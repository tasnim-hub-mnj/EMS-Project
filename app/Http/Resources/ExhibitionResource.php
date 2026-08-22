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
        $activeCopy = $this->copies()->where('copy_status', 'active')->first();

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
            'imageUrl' => $this->image ? asset('storage/' . ltrim($this->image, '/')) : null,

            'totalBooths' => $this->total_booths,
            'bookedBooths' => $this->total_booths - $this->available_booths,
            'mapBuilt' => $this->map_built,
            'mapTheme' => $this->getMapTheme(),

            // النسخة الحالية
            'currentEditionId' => $activeCopy
                ? $activeCopy->id . '-ed-' . $activeCopy->year
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
        $latestMap = $this->latestMap;

        if (!$latestMap || empty($latestMap->map_json)) {
            return null;
        }

        $mapJson = $latestMap->map_json;

        if (is_array($mapJson)) {
            return $mapJson['theme'] ?? null;
        }

        if (!is_string($mapJson)) {
            return null;
        }

        if (!Storage::disk('public')->exists($mapJson)) {
            return null;
        }

        $json = json_decode(Storage::disk('public')->get($mapJson), true);

        if (!is_array($json)) {
            return null;
        }

        return $json['theme'] ?? null;
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MapResource extends JsonResource
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
            'id' => 'map-' . $this->id,
            'exhibitionId' => (string) $this->exhibition_id,
            'version' => $this->version,
            'schemaVersion' => $this->schema_version,
            'publishedAt' => $this->published_at,
            'status' => $this->status,

            // هنا نرجّع الـ scene graph كما هو
            'scene' => $this->map_json['scene'] ?? null,
            'assets' => $this->map_json['assets'] ?? null,
            'instances' => $this->map_json['instances'] ?? [],
        ];
        
        // $map = $this->map_json;
        // return
        // [
        //     'id' => 'map-' . $this->id,
        //     'exhibitionId' => (string) $this->exhibition_id,
        //     'version' => $this->version,
        //     'schemaVersion' => $this->schema_version,
        //     'publishedAt' => $this->published_at,
        //     'status' => $this->status,

        //     'canvasWidth' => $map['canvasWidth'] ?? null,
        //     'canvasHeight' => $map['canvasHeight'] ?? null,
        //     'backgroundColor' => $map['backgroundColor'] ?? null,
        //     'theme' => $map['theme'] ?? null,
        //     'unit' => $map['unit'] ?? null,
        //     'metersPerUnit' => $map['metersPerUnit'] ?? null,
        //     'venue' => $map['venue'] ?? null,
        //     'floors' => $map['floors'] ?? [],
        //     'elements' => $map['elements'] ?? [],
        // ];
    }
}

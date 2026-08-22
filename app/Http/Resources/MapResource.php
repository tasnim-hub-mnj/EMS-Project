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
        if ($this->resource === null) {
            return [];
        }

        $mapData = is_array($this->map_json) ? $this->map_json : [];
        $scene = $mapData['scene'] ?? [];
        $floors = $mapData['floors'] ?? ($scene['floors'] ?? []);
        $instances = $mapData['instances'] ?? [];
        $elements = $mapData['elements'] ?? [];

        if (empty($elements) && !empty($instances)) {
            $elements = array_map(function ($instance) {
                $position = $instance['position'] ?? [];
                $rotation = $instance['rotation'] ?? [];
                $scale = $instance['scale'] ?? [];

                return [
                    'id' => $instance['id'] ?? uniqid('element_'),
                    'type' => $instance['type'] ?? 'booth',
                    'shape' => 'rect',
                    'label' => $instance['label'] ?? 'element',
                    'x' => isset($position['x']) ? (float) ($position['x'] / 0.01) : 0,
                    'y' => isset($position['z']) ? (float) ($position['z'] / 0.01) : 0,
                    'width' => isset($scale['x']) ? (float) ($scale['x'] / 0.01) : 1,
                    'height' => isset($scale['z']) ? (float) ($scale['z'] / 0.01) : 1,
                    'depth' => isset($scale['y']) ? (float) ($scale['y'] / 0.01) : 1,
                    'rotation' => isset($rotation['y']) ? (float) $rotation['y'] : 0,
                    'rotationY' => isset($rotation['y']) ? (float) ($rotation['y'] * 180 / pi()) : 0,
                    'fill' => $instance['fill'] ?? $instance['color'] ?? '#7dd3fc',
                    'stroke' => $instance['stroke'] ?? $instance['fill'] ?? '#7dd3fc',
                    'strokeWidth' => 1,
                    'floorId' => $instance['floor_id'] ?? 'floor-0',
                    'zIndex' => 1,
                    'model3d' => $instance['asset_key'] ?? 'booth_mod1',
                    'boothId' => $instance['id'] ?? null,
                    'metadata' => [
                        'area' => (isset($scale['x']) && isset($scale['z'])) ? (float) ($scale['x'] * $scale['z'] * 10) : 1,
                        'price' => 0,
                    ],
                ];
            }, $instances);
        }

        return
        [
            'id' => 'map-' . $this->id,
            'exhibitionId' => (string) $this->exhibition_id,
            'version' => $this->version,
            'schemaVersion' => $this->schema_version,
            'publishedAt' => $this->published_at,
            'status' => $this->status,
            'canvasWidth' => $mapData['canvasWidth'] ?? $scene['width'] ?? 1200,
            'canvasHeight' => $mapData['canvasHeight'] ?? $scene['height'] ?? 800,
            'backgroundColor' => $mapData['backgroundColor'] ?? $scene['backgroundColor'] ?? $scene['background_color'] ?? '#0b1020',
            'theme' => $mapData['theme'] ?? $scene['theme'] ?? 'modern',
            'unit' => $mapData['unit'] ?? $scene['unit'] ?? 'meters',
            'metersPerUnit' => $mapData['metersPerUnit'] ?? $scene['metersPerUnit'] ?? $scene['meters_per_unit'] ?? 1,
            'venue' => $mapData['venue'] ?? ['shape' => 'rect'],
            'floors' => $floors,
            'elements' => $elements,
            'scene' => $scene,
            'assets' => $mapData['assets'] ?? null,
            'instances' => $instances,
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

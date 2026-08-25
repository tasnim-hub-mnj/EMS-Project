<?php

namespace App\Http\Controllers;

use App\Http\Resources\SectionResource;
use App\Models\Booth;
use App\Models\Section;
use App\Models\Exhibition;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    protected function normalizeSectionTypeValue($value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return 'عام';
        }

        $validTypes = ['عام', 'تقنية', 'أغذية ومشروبات'];
        if (in_array($raw, $validTypes, true)) {
            return $raw;
        }

        $lower = strtolower($raw);
        if (str_contains($lower, 'tech') || str_contains($lower, 'technology') || str_contains($lower, 'تقنية')) {
            return 'تقنية';
        }

        if (str_contains($lower, 'food') || str_contains($lower, 'restaurant') || str_contains($lower, 'beverage') || str_contains($lower, 'مشروبات') || str_contains($lower, 'اغذية')) {
            return 'أغذية ومشروبات';
        }

        return 'عام';
    }

    public function index($exhibitionId)
    {
        $sections = Section::with('booths')->where('exhibition_id', $exhibitionId)->orderBy('name')->get();

        return SectionResource::collection($sections);
    }

    public function store(Request $request, $exhibitionId)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:80'],
            'width' => ['nullable', 'numeric'],
            'height' => ['nullable', 'numeric'],
            'map_x' => ['nullable', 'integer'],
            'map_y' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ]);

        $section = Section::updateOrCreate(
            [
                'exhibition_id' => $exhibitionId,
                'name' => trim($data['name']),
            ],
            [
                'type' => $data['type'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'map_x' => $data['map_x'] ?? null,
                'map_y' => $data['map_y'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]
        );

        $this->notifyMap($exhibitionId, 'تم تحديث أقسام المعرض', 'تمت إضافة أو تحديث قسم في خريطة المعرض.');

        return new SectionResource($section);
    }

    public function update(Request $request, $sectionId)
    {
        $section = Section::findOrFail($sectionId);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:80'],
            'width' => ['nullable', 'numeric'],
            'height' => ['nullable', 'numeric'],
            'map_x' => ['nullable', 'integer'],
            'map_y' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (!empty($data['name'])) {
            $data['name'] = trim($data['name']);
            $existing = Section::where('exhibition_id', $section->exhibition_id)
                ->where('name', $data['name'])
                ->whereKeyNot($section->id)
                ->first();

            if ($existing) {
                return response()->json(['message' => 'Section name already exists in this exhibition.'], 422);
            }
        }

        $section->fill($data);
        $section->save();

        if (!empty($data['name'])) {
            Booth::where('exhibition_id', $section->exhibition_id)
                ->where('section', $section->getOriginal('name'))
                ->update(['section' => $data['name']]);
        }

        $this->notifyMap($section->exhibition_id, 'تم تعديل قسم في الخريطة', 'تم تعديل بيانات أحد أقسام المعرض.');

        return new SectionResource($section->fresh());
    }

    public function destroy($sectionId)
    {
        $section = Section::with('booths')->findOrFail($sectionId);

        foreach ($section->booths as $booth) {
            $booth->delete();
        }

        $section->delete();

        $this->notifyMap($section->exhibition_id, 'تم حذف قسم من الخريطة', 'تم حذف قسم والأجنحة التابعة له.');

        return response()->json([
            'success' => true,
            'message' => 'Section and related booths deleted successfully.',
        ]);
    }

    private function notifyMap(int $exhibitionId, string $title, string $body): void
    {
        $exhibition = Exhibition::find($exhibitionId);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, $title, $body, 'map', 'org.map', [], '/map', ['admin.map']
            );
        }
    }

    public function sync(Request $request, $exhibitionId)
    {
        $validated = $request->validate([
            'sections' => ['nullable', 'array'],
            'booths' => ['nullable', 'array'],
        ]);

        $sectionMap = [];

        foreach ($validated['sections'] as $sectionInput) {
            $name = trim((string) ($sectionInput['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $sectionType = $sectionInput['type'] ?? ($sectionInput['metadata']['sectionType'] ?? null);
            $normalizedType = $this->normalizeSectionTypeValue($sectionType);

            $section = Section::updateOrCreate(
                [
                    'exhibition_id' => $exhibitionId,
                    'name' => $name,
                ],
                [
                    'type' => $normalizedType,
                    'width' => $sectionInput['width'] ?? null,
                    'height' => $sectionInput['height'] ?? null,
                    'map_x' => $sectionInput['map_x'] ?? $sectionInput['mapX'] ?? null,
                    'map_y' => $sectionInput['map_y'] ?? $sectionInput['mapY'] ?? null,
                    'metadata' => array_merge((array) ($sectionInput['metadata'] ?? []), ['sectionType' => $normalizedType]),
                ]
            );

            $sectionMap[$name] = $section->id;
        }

        $boothNames = [];
        foreach ($validated['booths'] ?? [] as $boothInput) {
            $sectionName = trim((string) ($boothInput['section'] ?? ''));
            $number = trim((string) ($boothInput['number'] ?? ''));

            if ($sectionName === '' || $number === '') {
                continue;
            }

            $sectionId = $sectionMap[$sectionName] ?? null;
            if (!$sectionId) {
                $section = Section::firstOrCreate(
                    ['exhibition_id' => $exhibitionId, 'name' => $sectionName],
                    ['type' => 'default']
                );
                $sectionId = $section->id;
                $sectionMap[$sectionName] = $sectionId;
            }

            $area = (float) ($boothInput['area'] ?? 0);
            $mapWidth = (float) ($boothInput['map_width'] ?? $boothInput['mapWidth'] ?? 0);
            $mapHeight = (float) ($boothInput['map_height'] ?? $boothInput['mapHeight'] ?? 0);
            if ($mapWidth > 0 && $mapHeight > 0) {
                $area = ($mapWidth * $mapHeight) / 1000;
            }

            $boothData = [
                'section_id' => $sectionId,
                'section' => $sectionName,
                'area' => $area,
                'location' => $boothInput['location'] ?? null,
                'map_x' => $boothInput['map_x'] ?? $boothInput['mapX'] ?? null,
                'map_y' => $boothInput['map_y'] ?? $boothInput['mapY'] ?? null,
                'map_width' => $boothInput['map_width'] ?? $boothInput['mapWidth'] ?? null,
                'map_height' => $boothInput['map_height'] ?? $boothInput['mapHeight'] ?? null,
            ];

            if (array_key_exists('description', $boothInput) && Schema::hasColumn('booths', 'description')) {
                $boothData['description'] = $boothInput['description'];
            }

            $booth = Booth::where('exhibition_id', $exhibitionId)
                ->where('number', $number)
                ->where('section', $sectionName)
                ->first();

            if (!$booth) {
                $sameNumberBooths = Booth::where('exhibition_id', $exhibitionId)
                    ->where('number', $number)
                    ->get();
                $booth = $sameNumberBooths->count() === 1
                    ? $sameNumberBooths->first()
                    : Booth::firstOrNew(
                        [
                            'exhibition_id' => $exhibitionId,
                            'number' => $number,
                            'section' => $sectionName,
                        ]
                    );
            }

            if (!$booth->exists) {
                $boothData['status'] = $boothInput['status'] ?? 'available';
                $boothData['price'] = $boothInput['price'] ?? 0;
                $boothData['services'] = $boothInput['services'] ?? [];
                $boothData['amenities'] = $boothInput['amenities'] ?? [];
            }

            $booth->fill($boothData);
            $booth->save();

            $boothNames[] = $booth->id;
        }

        $currentSectionNames = array_keys($sectionMap);

        if (empty($currentSectionNames)) {
            Section::where('exhibition_id', $exhibitionId)->delete();
        } else {
            $deletedSections = Section::where('exhibition_id', $exhibitionId)
                ->whereNotIn('name', $currentSectionNames)
                ->get();

            foreach ($deletedSections as $section) {
                foreach ($section->booths as $booth) {
                    $booth->delete();
                }
                $section->delete();
            }
        }

        $existingBooths = Booth::where('exhibition_id', $exhibitionId)->get();
        if (empty($validated['booths'] ?? [])) {
            foreach ($existingBooths as $booth) {
                $booth->delete();
            }
        } else {
            foreach ($existingBooths as $booth) {
                $shouldKeep = false;
                foreach ($validated['booths'] ?? [] as $boothInput) {
                    if ((string) ($boothInput['number'] ?? '') === (string) $booth->number && (string) ($boothInput['section'] ?? '') === (string) $booth->section) {
                        $shouldKeep = true;
                        break;
                    }
                }

                if (!$shouldKeep) {
                    $booth->delete();
                }
            }
        }

        $this->notifyMap($exhibitionId, 'تمت مزامنة خريطة المعرض', 'تم تحديث الأقسام والأجنحة في خريطة المعرض.');

        return response()->json([
            'success' => true,
            'sections' => Section::where('exhibition_id', $exhibitionId)->get()->map->only(['id', 'name', 'type']),
            'booths' => Booth::where('exhibition_id', $exhibitionId)->count(),
        ]);
    }
}

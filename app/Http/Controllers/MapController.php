<?php

namespace App\Http\Controllers;

use App\Http\Requests\RawJsonMapRequest;
use App\Http\Requests\SaveMapRequest;
use App\Http\Requests\UpdateMapRequest;
use App\Http\Resources\MapResource;
use App\Models\Map;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MapController extends Controller
{
    public function show($exhibition_id)//يرجّع آخر نسخة حسب الـ version.
    {
        $map = Map::where('exhibition_id', $exhibition_id)
            ->orderByDesc('version')
            ->first();

        return new MapResource($map);
    }
    //=========================================================
    public function store(SaveMapRequest $request, $exhibition_id)
    {
        $json = json_decode($request->file('map')->getContent(), true);
        // $path = $request->file('map')->store('maps', 'public');

        $latestVersion = Map::where('exhibition_id', $exhibition_id)->max('version') ?? 0;

        $map = Map::create([
            'exhibition_id' => $exhibition_id,
            'version' => $latestVersion + 1,
            'schema_version' => $request->schema_version,
            'map_json' => $json,
            'created_by' => Auth::id(),
            'status' => 'draft'
        ]);

        return new MapResource($map);
    }
    //=========================================================
    public function update(UpdateMapRequest $request, $exhibition_id, $map_id)
    {
        $map = Map::where('exhibition_id', $exhibition_id)->findOrFail($map_id);

        $json = json_decode($request->file('map')->getContent(), true);
        // $path = $request->file('map')->store('maps', 'public');

        // if ($map->map_json)
        // {
        //     Storage::disk('public')->delete($map->map_json);
        // }

        $map->update([
            'map_json' => $json
        ]);

        return new MapResource($map);
    }
    //=========================================================
    public function saveRaw(RawJsonMapRequest $request, $exhibition_id)
    {
        // $path = $request->file('map')->store('maps', 'public');
        $latestVersion = Map::where('exhibition_id', $exhibition_id)->max('version') ?? 0;

        $map = Map::create([
            'exhibition_id' => $exhibition_id,
            'version' => $latestVersion + 1,
            'schema_version' => 1,
            'map_json' => $request->all(),
            'created_by' => Auth::id(),
            'status' => 'draft'
        ]);

        return new MapResource($map);
    }
    //=========================================================
    public function history($exhibition_id)
    {
        $maps = Map::where('exhibition_id', $exhibition_id)
            ->orderByDesc('version')
            ->get()
            ->map(fn($m) =>
            [
                'id' => 'map-' . $m->id,
                'version' => $m->version,
                'publishedAt' => $m->published_at,
                'status' => $m->status,
                'createdBy' => 'user_' . $m->created_by
            ]);

        return $maps;
    }
    //=========================================================
    public function publish($exhibition_id, $map_id)
    {
        $map = Map::where('exhibition_id', $exhibition_id)->findOrFail($map_id);

        $map->update([
            'status' => 'published',
            'published_at' => now()
        ]);

        return [
            'success' => true,
            'message' => 'Map published successfully'
        ];
    }
    //=========================================================
}

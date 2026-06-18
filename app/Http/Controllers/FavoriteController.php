<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function addToFavorite(Request $request)
    {
        $request->validate([
            'type' => 'required|in:exhibition,booth,sponsor_event',
            'id'   => 'required|integer'
        ]);

        $models =
        [
            'exhibition' => \App\Models\Exhibition::class,
            'booth'      => \App\Models\Booth::class,
            'sponsor_event' => \App\Models\SponsorEvent::class,
        ];

        $model = $models[$request->type];
        $item  = $model::findOrFail($request->id);

        // منع التكرار
        $exists = Favorite::where('investor_id', Auth::id())
                        ->where('favoritable_id', $item->id)
                        ->where('favoritable_type', $model)
                        ->exists();

        if ($exists)
        {
            return response()->json(['message' => 'Already in favorites'], 200);
        }

        Favorite::create([
            'investor_id'          => Auth::id(),
            'favoritable_id'   => $item->id,
            'favoritable_type' => $model,
        ]);

        return response()->json([
            'message' => 'Added to favorites'
        ], 201);
    }
    //==========================================================================
    public function removeFromFavorite(Request $request)
    {
        $request->validate([
            'type' => 'required|in:exhibition,booth,sponsor_event',
            'id'   => 'required|integer'
        ]);

        $models =
        [
            'exhibition' => \App\Models\Exhibition::class,
            'booth'      => \App\Models\Booth::class,
            'sponsor_event' => \App\Models\SponsorEvent::class,
        ];

        $model = $models[$request->type];

        Favorite::where('investor_id', Auth::id())
                ->where('favoritable_id', $request->id)
                ->where('favoritable_type', $model)
                ->delete();

        return response()->json([
            'message' => 'Removed from favorites'
        ], 200);
    }

    //==========================================================================
    public function favoriteExhibitions()
    {
        $favorites = Favorite::where('investor_id', Auth::id())
            ->where('favoritable_type', \App\Models\Exhibition::class)
            ->with('favoritable')
            ->get();

        return response()->json([
            'count' => $favorites->count(),
            'items' => $favorites->pluck('favoritable')
        ], 200);
    }
    //==========================================================================
    public function favoriteBooths()
    {
        $favorites = Favorite::where('investor_id', Auth::id())
            ->where('favoritable_type', \App\Models\Booth::class)
            ->with('favoritable')
            ->get();

        return response()->json([
            'count' => $favorites->count(),
            'items' => $favorites->pluck('favoritable')
        ], 200);
    }

    //==========================================================================
    public function favoriteEvents()
    {
        $favorites = Favorite::where('investor_id', Auth::id())
            ->where('favoritable_type', \App\Models\SponsorEvent::class)
            ->with('favoritable')
            ->get();

        return response()->json([
            'count' => $favorites->count(),
            'items' => $favorites->pluck('favoritable')
        ], 200);
    }

    //==========================================================================
    //==========================================================================

}

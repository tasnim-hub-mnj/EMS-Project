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
            'type' => 'required|in:exhibition,booth,sponsor_event,event',
            'id'   => 'required|integer'
        ]);

        $models =
        [
            'exhibition' => \App\Models\Exhibition::class,
            'booth'      => \App\Models\Booth::class,
            'sponsor_event' => \App\Models\SponsorEvent::class,
            'event' => \App\Models\Event::class,
        ];

        $model = $models[$request->type];
        $item  = $model::findOrFail($request->id);

        // منع التكرار
        $exists = Favorite::where('user_id', Auth::id())
            ->where('favoritable_id', $item->id)
            ->where('favoritable_type', $model)
            ->exists();

        if ($exists)
        {
            return response()->json(['message' => 'Already in favorites'], 200);
        }

        Favorite::create([
            'user_id' => Auth::id(),
            'favoritable_id' => $item->id,
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
            'type' => 'required|in:exhibition,booth,sponsor_event,event',
            'id'   => 'required|integer'
        ]);

        $models = [
            'exhibition' => \App\Models\Exhibition::class,
            'booth'      => \App\Models\Booth::class,
            'sponsor_event' => \App\Models\SponsorEvent::class,
            'event' => \App\Models\Event::class,
        ];

        $model = $models[$request->type];

        Favorite::where('user_id', Auth::id())
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
        $favorites = Favorite::where('user_id', Auth::id())
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
        $favorites = Favorite::where('user_id', Auth::id())
            ->where('favoritable_type', \App\Models\Booth::class)
            ->with('favoritable')
            ->get();

        return response()->json([
            'count' => $favorites->count(),
            'items' => $favorites->pluck('favoritable')
        ], 200);
    }

    //==========================================================================
    public function favoriteSponsorEvents()
    {
        $favorites = Favorite::where('user_id', Auth::id())
            ->where('favoritable_type', \App\Models\SponsorEvent::class)
            ->with('favoritable')
            ->get();

        return response()->json([
            'count' => $favorites->count(),
            'items' => $favorites->pluck('favoritable')
        ], 200);
    }
    //==========================================================================
    public function favoriteAllEvents()
    {
        $user_id = Auth::id();

        $favorites = Favorite::where('user_id', $user_id)
            ->whereIn('favoritable_type', [
                \App\Models\Event::class,
                \App\Models\SponsorEvent::class
            ])
            ->with('favoritable')
            ->get();

        // تجهيز البيانات
        $items = $favorites->map(function ($fav)
        {
            return [
                'type' => class_basename($fav->favoritable_type), // Event أو SponsorEvent
                'data' => $fav->favoritable
            ];
        });

        return response()->json([
            'count' => $items->count(),
            'items' => $items
        ], 200);
    }
    //==========================================================================

}

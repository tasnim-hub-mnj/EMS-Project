<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function addFavorite(Request $request, $id)//✅
    {
        $type = $request->query('type');

        $validTypes =
        [
            'exhibition' => \App\Models\Exhibition::class,
            'booth'      => \App\Models\Booth::class,
            'event'      => \App\Models\Event::class,
            'sponsor_event' => \App\Models\SponsorEvent::class,
        ];

        if (!isset($validTypes[$type]))
        {
            return response()->json(['message' => 'Invalid type'], 422);
        }

        $model = $validTypes[$type];

        // منع التكرار
        $exists = Favorite::where('user_id', Auth::id())
            ->where('favoritable_id', $id)
            ->where('favoritable_type', $model)
            ->exists();

        if ($exists)
        {
            return response()->json(['message' => 'Already in favorites'], 200);
        }

        Favorite::create([
            'user_id' => Auth::id(),
            'favoritable_id' => $id,
            'favoritable_type' => $model,
        ]);

        return response()->json([
            'message' => 'Added to favorites'
        ], 201);
    }
    //==========================================================================
    public function removeFavorite(Request $request, $id)//✅
    {
        $type = $request->query('type');

        $validTypes =
        [
            'exhibition' => \App\Models\Exhibition::class,
            'booth'      => \App\Models\Booth::class,
            'event'      => \App\Models\Event::class,
            'sponsor_event' => \App\Models\SponsorEvent::class,
        ];

        if (!isset($validTypes[$type]))
        {
            return response()->json(['message' => 'Invalid type'], 422);
        }

        $model = $validTypes[$type];

        Favorite::where('user_id', Auth::id())
            ->where('favoritable_id', $id)
            ->where('favoritable_type', $model)
            ->delete();

        return response()->json([
            'message' => 'Removed from favorites'
        ], 200);
    }
    //==========================================================================
    public function getFavoritesInvestor()//✅
    {
        $userId = Auth::id();

        $favorites = Favorite::where('user_id', $userId)
            ->with('favoritable')
            ->get();

        // تصنيف حسب النوع
        $exhibitions = [];
        $booths = [];
        $events = [];

        foreach ($favorites as $fav)
        {
            $type = class_basename($fav->favoritable_type);

            if ($type === 'Exhibition')
            {
                $exhibitions[] = $fav->favoritable;
            }

            if ($type === 'Booth')
            {
                $booths[] = $fav->favoritable;
            }

            if ($type === 'SponsorEvent')
            {
                $events[] = $fav->favoritable;
            }

        }

        return response()->json([
            'exhibitions' => $exhibitions,
            'booths' => $booths,
            'events' => $events,
        ], 200);
    }
    //==========================================================================
    public function getFavoritesVisitor()
    {
        $userId = Auth::id();

        $favorites = Favorite::where('user_id', $userId)
            ->with('favoritable')
            ->get();

        // تصنيف حسب النوع
        $exhibitions = [];
        $booths = [];
        $events = []; // Event + SponsorEvent

        foreach ($favorites as $fav)
        {
            $type = class_basename($fav->favoritable_type);

            if ($type === 'Exhibition')
            {
                $exhibitions[] = $fav->favoritable;
            }

            if ($type === 'Booth')
            {
                $booths[] = $fav->favoritable;
            }

            if ($type === 'Event' || $type === 'SponsorEvent')
            {
                $events[] =
                [
                    'type' => $type,
                    'data' => $fav->favoritable
                ];
            }
        }

        return response()->json([
            'exhibitions' => $exhibitions,
            'booths' => $booths,
            'events' => $events
        ], 200);
    }
    //==========================================================================
    //==========================================================================
    // public function favoriteExhibitions()
    // {
    //     $favorites = Favorite::where('user_id', Auth::id())
    //         ->where('favoritable_type', \App\Models\Exhibition::class)
    //         ->with('favoritable')
    //         ->get();

    //     return response()->json([
    //         'count' => $favorites->count(),
    //         'items' => $favorites->pluck('favoritable')
    //     ], 200);
    // }
    // //==========================================================================
    // public function favoriteBooths()
    // {
    //     $favorites = Favorite::where('user_id', Auth::id())
    //         ->where('favoritable_type', \App\Models\Booth::class)
    //         ->with('favoritable')
    //         ->get();

    //     return response()->json([
    //         'count' => $favorites->count(),
    //         'items' => $favorites->pluck('favoritable')
    //     ], 200);
    // }

    // //==========================================================================
    // public function favoriteSponsorEvents()
    // {
    //     $favorites = Favorite::where('user_id', Auth::id())
    //         ->where('favoritable_type', \App\Models\SponsorEvent::class)
    //         ->with('favoritable')
    //         ->get();

    //     return response()->json([
    //         'count' => $favorites->count(),
    //         'items' => $favorites->pluck('favoritable')
    //     ], 200);
    // }
    // //==========================================================================
    // public function favoriteAllEvents()
    // {
    //     $user_id = Auth::id();

    //     $favorites = Favorite::where('user_id', $user_id)
    //         ->whereIn('favoritable_type', [
    //             \App\Models\Event::class,
    //             \App\Models\SponsorEvent::class
    //         ])
    //         ->with('favoritable')
    //         ->get();

    //     // تجهيز البيانات
    //     $items = $favorites->map(function ($fav)
    //     {
    //         return [
    //             'type' => class_basename($fav->favoritable_type), // Event أو SponsorEvent
    //             'data' => $fav->favoritable
    //         ];
    //     });

    //     return response()->json([
    //         'count' => $items->count(),
    //         'items' => $items
    //     ], 200);
    // }
    // //==========================================================================

}

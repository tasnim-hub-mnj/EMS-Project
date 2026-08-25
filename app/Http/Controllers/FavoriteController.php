<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\Favorite;
use App\Models\SponsorEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    private function favoriteTypes(): array
    {
        return [
            'exhibition' => \App\Models\Exhibition::class,
            'booth' => Booth::class,
            'event' => \App\Models\Event::class,
            'sponsor_event' => SponsorEvent::class,
        ];
    }

    public function addFavorite(Request $request, $id)
    {
        $type = $request->query('type');
        $model = $this->favoriteTypes()[$type] ?? null;
        if (!$model) return response()->json(['message' => 'Invalid type'], 422);

        $exists = Favorite::where('user_id', Auth::id())
            ->where('favoritable_id', $id)
            ->where('favoritable_type', $model)
            ->exists();
        if ($exists) return response()->json(['message' => 'Already in favorites'], 200);

        Favorite::create([
            'user_id' => Auth::id(),
            'favoritable_id' => $id,
            'favoritable_type' => $model,
        ]);
        return response()->json(['message' => 'Added to favorites'], 201);
    }

    public function removeFavorite(Request $request, $id)
    {
        $type = $request->query('type');
        $model = $this->favoriteTypes()[$type] ?? null;
        if (!$model) return response()->json(['message' => 'Invalid type'], 422);

        Favorite::where('user_id', Auth::id())
            ->where('favoritable_id', $id)
            ->where('favoritable_type', $model)
            ->delete();
        return response()->json(['message' => 'Removed from favorites'], 200);
    }

    public function getFavoritesInvestor()
    {
        $favorites = Favorite::where('user_id', Auth::id())
            ->with('favoritable')
            ->get();
        $exhibitions = [];
        $booths = [];
        $events = [];

        foreach ($favorites as $favorite) {
            if (!$favorite->favoritable) continue;
            $type = class_basename($favorite->favoritable_type);
            if ($type === 'Exhibition') {
                $exhibition = $favorite->favoritable->toArray();
                $exhibition['is_favorite'] = true;
                $exhibitions[] = $exhibition;
            }
            if ($type === 'Booth') $booths[] = $this->favoriteBooth($favorite->favoritable);
            if ($type === 'SponsorEvent') $events[] = $this->favoriteSponsorEvent($favorite->favoritable);
        }

        return response()->json([
            'status' => true,
            'exhibitions' => $exhibitions,
            'booths' => $booths,
            'events' => $events,
        ], 200);
    }

    private function favoriteBooth(Booth $booth): array
    {
        $booth->loadMissing(['exhibition', 'boothImages', 'boothBookings']);
        $sectionName = $booth->getRawOriginal('section')
            ?: $booth->section()?->first()?->name;
        $investorId = Auth::user()->investor?->id;
        $ownBooking = $booth->boothBookings
            ->where('investor_id', $investorId)
            ->sortByDesc('created_at')
            ->first();
        $publicStatus = (string) ($booth->status_inv ?? $booth->status ?? 'available');
        $status = $ownBooking
            ? $this->investorBoothStatus($ownBooking)
            : ($publicStatus === 'booked' ? 'booked' : $publicStatus);
        $services = $booth->services ?? [];
        if (is_string($services)) $services = json_decode($services, true) ?: [];

        return [
            'id' => (int) $booth->id,
            'number' => (string) $booth->number,
            'exhibition_id' => (int) $booth->exhibition_id,
            'exhibition_name' => $booth->exhibition?->name,
            'image_url' => $this->publicImageUrl($booth->boothImages->first()?->image),
            'area' => (float) $booth->area,
            'status' => $status,
            'status_inv' => $publicStatus,
            'price' => (float) $booth->price,
            'pricing_type' => $booth->pricing_type,
            'start_date' => $booth->exhibition?->start_date
                ? Carbon::parse($booth->exhibition->start_date)->toDateString() : '',
            'end_date' => $booth->exhibition?->end_date
                ? Carbon::parse($booth->exhibition->end_date)->toDateString() : '',
            'location' => $booth->location,
            'section' => $sectionName,
            'amenities' => $booth->amenities ?? [],
            'services' => is_array($services) ? $services : [],
            'is_favorite' => true,
        ];
    }

    private function investorBoothStatus(BoothBooking $booking): string
    {
        if ($booking->status === 'approved') {
            return $booking->end_date && Carbon::parse($booking->end_date)->isPast()
                ? 'ended' : 'active';
        }

        return match ($booking->status) {
            'pending' => 'pending',
            'rejected' => 'rejected',
            'cancelled', 'canceled' => 'cancelled',
            'finished' => 'ended',
            default => (string) $booking->status,
        };
    }

    private function favoriteSponsorEvent(SponsorEvent $event): array
    {
        $event->loadMissing(['exhibition.exhibitionImages', 'sponsorEventImages', 'programs']);
        $options = $event->duration_options ?? [];
        if (is_string($options)) $options = json_decode($options, true) ?: [];
        if (!is_array($options) || empty($options)) {
            $options = $event->daily_price === null ? [] : [[
                'label' => 'يوم واحد',
                'days' => 1,
                'start_date' => Carbon::parse($event->start_time)->toDateString(),
                'end_date' => Carbon::parse($event->start_time)->toDateString(),
                'price' => (float) $event->daily_price,
            ]];
        }
        $options = array_map(function ($option) {
            $days = (int) ($option['days'] ?? 1);
            return [
                'label' => $option['label'] ?? ($days === 1 ? 'يوم واحد' : "{$days} أيام"),
                'days' => $days,
                'start_date' => $option['start_date'] ?? null,
                'end_date' => $option['end_date'] ?? null,
                'price' => (float) ($option['price'] ?? 0),
            ];
        }, $options);

        return [
            'id' => (int) $event->id,
            'name' => $event->name,
            'type' => $event->type,
            'exhibition_id' => (int) $event->exhibition_id,
            'exhibition_name' => $event->exhibition?->name,
            'exhibition_image_url' => $this->publicImageUrl(
                $event->exhibition?->exhibitionImages?->first()?->image
            ),
            'image_url' => $this->publicImageUrl($event->sponsorEventImages->first()?->image),
            'date' => Carbon::parse($event->start_time)->toDateString(),
            'start_time' => Carbon::parse($event->start_time)->format('H:i'),
            'end_time' => Carbon::parse($event->end_time)->format('H:i'),
            'place' => $event->place,
            'hall' => $event->place,
            'booth' => '',
            'listing_days' => (int) ($event->duration_days ?? 1),
            'description' => $event->description,
            'capacity' => (int) $event->max_participants,
            'available_seats' => max(0, (int) $event->max_participants - (int) $event->registered_count),
            'total_seats' => (int) $event->max_participants,
            'registered_count' => (int) $event->registered_count,
            'scanned_count' => (int) $event->scanned_count,
            'ticket_type' => $event->ticket_type,
            'ticket_price' => (float) $event->ticket_price,
            'status' => $event->status,
            'publish_date' => $event->publish_date,
            'daily_price' => $event->daily_price,
            'duration_options' => array_values($options),
            'durationOptions' => array_values($options),
            'images' => $event->sponsorEventImages->map(fn ($image) => [
                'id' => $image->id,
                'url' => $this->publicImageUrl($image->image),
                'caption' => $image->caption,
            ])->values()->all(),
            'activities' => $event->programs->map(fn ($program) => [
                'id' => $program->id,
                'title' => $program->title,
                'start_time' => $program->start_time,
                'end_time' => $program->end_time,
            ])->values()->all(),
            'is_favorite' => true,
            'event_source' => 'sponsor_event',
        ];
    }

    public function getFavoritesVisitor()
    {
        $favorites = Favorite::where('user_id', Auth::id())->with('favoritable')->get();
        $exhibitions = [];
        $booths = [];
        $events = [];
        foreach ($favorites as $favorite) {
            if (!$favorite->favoritable) continue;
            $type = class_basename($favorite->favoritable_type);
            if ($type === 'Exhibition') $exhibitions[] = $favorite->favoritable;
            if ($type === 'Booth') {
                $booths[] = $this->favoriteBoothForVisitor($favorite->favoritable);
            }
            if ($type === 'SponsorEvent') {
                $events[] = [
                    'type' => $type,
                    'data' => $this->favoriteSponsorEvent($favorite->favoritable),
                ];
            } elseif ($type === 'Event') {
                $events[] = [
                    'type' => $type,
                    'data' => array_merge($favorite->favoritable->toArray(), [
                        'event_source' => 'event',
                    ]),
                ];
            }
        }
        return response()->json([
            'status' => true,
            'exhibitions' => $exhibitions,
            'booths' => $booths,
            'events' => $events,
        ], 200);
    }

    private function favoriteBoothForVisitor(Booth $booth): array
    {
        $data = $this->favoriteBooth($booth);
        $services = $data['services'];
        $serviceNames = [];
        if (is_array($services)) {
            $serviceNames = array_is_list($services)
                ? array_map(fn ($value) => (string) $value, $services)
                : array_map(fn ($key) => (string) $key, array_keys($services));
        }

        return array_merge($data, [
            'booth_number' => $data['number'],
            'hall' => $data['location'] ?? '',
            'booth_image' => $data['image_url'],
            'booth_images' => $data['image_url'] ? [$data['image_url']] : [],
            'services' => $serviceNames,
        ]);
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (!$path) return null;
        return str_starts_with($path, 'data:') || filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : asset('storage/' . ltrim($path, '/'));
    }
}

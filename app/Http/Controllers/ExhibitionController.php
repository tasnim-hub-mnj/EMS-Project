<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExhibitionRequest;
use App\Http\Requests\UpdateExhibitionRequest;
use App\Models\Booth;
use App\Models\Event;
use App\Models\Exhibition;
use App\Models\ExhibitionImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
//use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ExhibitionController extends Controller
{
    public function store(StoreExhibitionRequest $request)// اضافة معرض
    {
        $organizer = Auth::user()->organizer;
        $validate_data = $request->validated();
        $validate_data['organizer_id'] = $organizer->id;
        $validate_data['type'] = $organizer->category;
        $validate_data['location'] = $organizer->location;
        $validate_data['map'] = json_encode($validate_data['map']);

        // if ($request->hasFile('map'))
        // {
        //     $map = $request->file('map');
        //     $map_path = $map->store('maps', 'public');
        //     $validate_data['map'] = $map_path;
        // }

        $exhibition = Exhibition::create($validate_data);

        return response()->json([
            'message' => 'Exhibition created successfully',
            'exhibition' => $exhibition
        ], 201);
    }
    //===============================================================
    public function StoreImages(Request $request, $exhibition_id)//اضافة صورة للمعرض
    {
        $request->validate([
            'image' => 'required',
            'image.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);
        $images = [];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $img) {
                $path = $img->store('exhibition_images', 'public');

                $images[] = ExhibitionImage::create([
                    'exhibition_id' => $exhibition_id,
                    'image' => $path
                ]);
            }
        }

        return response()->json([
            'message' => 'Images Exhibition stored successfully',
            'images' => $images
        ], 201);
    }
    //===============================================================
    public function update(UpdateExhibitionRequest $request, $exhibition_id)//تعديل معرض
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
            ->findOrFail($exhibition_id);

        $exhibition->update($request->validated());

        return response()->json([
            'message' => 'Exhibition updated successfully',
            'exhibition' => $exhibition
        ], 200);
    }
    //===============================================================
    public function destroy($exhibition_id)//حذف معرض
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
            ->findOrFail($exhibition_id);

        $exhibition->delete();

        return response()->json([
            'message' => 'Exhibition deleted successfully'
        ], 200);
    }
    //===============================================================
    // public function featurrdExhibitionsI()//عرض المعارض المميزة للمستثمر
    // {
    //     $invsetor_user = Auth::user()->investor;
    //     $exhibitions = Exhibition::where('copy_status', 'active')
    //         ->where('location', $invsetor_user->location)
    //         ->where('type', $invsetor_user->activity_type)
    //         ->whereIn('status', ['upcoming', 'ongoing'])
    //         ->where('available_booths', '>', 0)
    //         ->orderBy('start_date', 'asc')
    //         ->get();

    //     return response()->json([
    //         'exhibitions' => $exhibitions
    //     ], 200);
    // }
    //===============================================================
    public function getAllExhibitions(Request $request)//✅
    {
        $page = $request->query('page', 1);
        $per_page = $request->query('per_page', 15);

        $search = $request->query('search');
        $city = $request->query('city');
        $sector = $request->query('sector');
        $status = $request->query('status'); // upcoming | active | ended

        $query = Exhibition::with([
            'sponsorEvents'
        ])->where('copy_status', 'active');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                    ->orWhere('city', 'LIKE', "%$search%");
            });
        }

        if ($city) {
            $query->where('city', $city);
        }

        if ($sector) {
            $query->whereJsonContains('sectors', $sector);

        }

        // Status filter (mapping API → DB)
        if ($status) {
            $statusMap =
                [
                    'upcoming' => 'upcoming',
                    'active' => 'ongoing',
                    'ended' => 'finished'
                ];

            if (isset($statusMap[$status])) {
                $query->where('status', $statusMap[$status]);
            }
        }

        $query->orderBy('start_date', 'asc');

        $exhibitions = $query->paginate($per_page, ['*'], 'page', $page);
        $user = auth('sanctum')->user();

        $exhibitions_data = $exhibitions->map(function ($exhibition) use ($user) {
            return
                [
                    'id' => $exhibition->id,
                    'name' => $exhibition->name,
                    'description' => $exhibition->description,
                    'images' => $exhibition->images ?? [],
                    'services' => $exhibition->extra_services
                        ? collect($exhibition->extra_services, true)->pluck('name')->toArray()
                        : [],
                    'mapJson' => $exhibition->map,
                    'sponsorEvents' => $exhibition->sponsorEvents,
                    'start_date' => Carbon::parse($exhibition->start_date)->format('Y-m-d'),
                    'end_date' => Carbon::parse($exhibition->end_date)->format('Y-m-d'),
                    'location' => $exhibition->location,
                    'city' => $exhibition->city,
                    'status' => $exhibition->status,
                    'available_booths' => $exhibition->available_booths,
                    'sectors' => $exhibition->sectors ?? [],
                    'is_favorite' => $user ? $user->favorites()
                        ->where('favoritable_id', $exhibition->id)
                        ->where('favoritable_type', Exhibition::class)
                        ->exists() : false,
                ];
        });

        return response()->json([
            'data' => $exhibitions_data,
            'pagination' =>
                [
                    'current_page' => $exhibitions->currentPage(),
                    'per_page' => $exhibitions->perPage(),
                    'total' => $exhibitions->total(),
                    'last_page' => $exhibitions->lastPage(),
                ]
        ], 200);
    }
    //===============================================================
    public function show($exhibition_id)//✅
    {
        $user = Auth::user();

        $exhibition = Exhibition::with([
            'sponsorEvents'
        ])->find($exhibition_id);

        if (!$exhibition) {
            return response()->json(['message' => 'Exhibition not found'], 404);
        }

        $is_favorite = $user->favorites()
            ->where('favoritable_id', $exhibition_id)
            ->where('favoritable_type', Exhibition::class)
            ->exists();

        $services = $exhibition->extra_services
            ? collect($exhibition->extra_services, true)->pluck('name')->toArray()
            : [];

        $images = $exhibition->exhibitionImages ?? [];

        $map_data = $exhibition->map;

        $sponsor_events = $exhibition->sponsorEvents->map(function ($event) {
            return
                [
                    'id' => $event->id,
                    'name' => $event->name,
                    'type' => $event->type,
                ];
        });

        return response()->json([
            'id' => $exhibition->id,
            'name' => $exhibition->name,
            'description' => $exhibition->description,
            'images' => $images,
            'services' => $services,
            'start_date' => Carbon::parse($exhibition->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($exhibition->end_date)->format('Y-m-d'),
            'location' => $exhibition->location,
            'city' => $exhibition->city,
            'status' => $exhibition->status,
            'available_booths' => $exhibition->available_booths,
            'sectors' => $exhibition->sectors ?? [],
            'is_favorite' => $is_favorite,
            'map_data' => $map_data,
            'sponsor_events' => $sponsor_events,
        ], 200);
    }
    //===============================================================
    public function archive($exhibition_id)//ارشفة معرض
    {
        $exhibition = Exhibition::where('organizer_id', Auth::id())
            ->findOrFail($exhibition_id);

        $exhibition->update([
            'copy_status' => 'archived'
        ]);

        return response()->json([
            'message' => 'Exhibition archived successfully',
            'exhibition' => $exhibition
        ], 200);
    }
    //===============================================================
    public function getMyExhibition($exhibition_id)//عرض المعرض الخاص بي
    {
        $organizer = Auth::user()->organizer;

        $exhibition = Exhibition::where('organizer_id', $organizer->id)
            ->with('booths')
            ->findOrFail($exhibition_id);

        return response()->json([
            'exhibition' => $exhibition
        ], 200);
    }

    //===============================================================
    public function getMap($exhibition_id)//عرض خريطة معرض
    {
        $organizer = Auth::user()->organizer;

        $exhibition = Exhibition::where('organizer_id', $organizer->id)
            ->findOrFail($exhibition_id);
        $map = json_decode($exhibition->map, true);

        return response()->json([
            'map' => $map,
        ], 200);

    }
    //===============================================================
    //===============================================================
    public function ongoing()//الجارية
    {
        $exhibitions = Exhibition::where('status', 'ongoing')
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);
    }
    //===============================================================
    public function finished()//المنتهية
    {
        $exhibitions = Exhibition::where('status', 'finished')
            ->orderBy('end_date', 'desc')
            ->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);
    }
    //===============================================================
    public function upcoming()//القادمة
    {
        $exhibitions = Exhibition::where('status', 'upcoming')
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json([
            'exhibitions' => $exhibitions
        ], 200);
    }

    //===============================================================
    //=========================الزائر===============================
    public function featuredExhibitionsForVisitor(Request $request)
    {
        $visitor = Auth::user()?->visitor;
        $interests = $visitor->interests ?? [];
        $city = $visitor->city ?? null;

        $isFeatured = $request->query('featured', 0);
        $perPage = (int) $request->query('per_page', 4);

        // جلب متوسط التقييم، عدد الأكشاك، عدد التذاكر المقبولة، وعدد الفعاليات
        $query = Exhibition::with(['exhibitionImages'])
            ->withCount([
                'booths',
                'tickets' => function ($ticketQuery) {
                    $ticketQuery->where('status', 'approved');
                },
                'sponsorEvents' // إضافة العلاقة هنا
            ])
            ->withAvg('exhibitionReviews as average_rating', 'rating');

        if (\Schema::hasColumn('exhibitions', 'copy_status')) {
            $query->where('copy_status', 'active');
        }

        if (\Schema::hasColumn('exhibitions', 'status')) {
            $query->whereIn('status', ['upcoming', 'ongoing']);
        }

        if ($isFeatured == 1) {
            if (!empty($interests)) {
                $query->where(function ($innerQuery) use ($interests) {
                    foreach ($interests as $interest) {
                        $innerQuery->orWhereJsonContains('sectors', $interest);
                    }
                });
            }

            if ($city) {
                $query->orderByRaw("city = ? DESC", [$city]);
            }

            $query->orderBy('tickets_count', 'desc'); // الترتيب بحسب التذاكر المقبولة مباشرةً
        }

        $exhibitions = $query->limit($perPage)->get();

        // الاستعلام الاحتياطي في حال عدم مطابقة الفلاتر أعلاه
        if ($exhibitions->isEmpty()) {
            $exhibitions = Exhibition::with(['exhibitionImages'])
                ->withCount([
                    'booths',
                    'tickets' => function ($ticketQuery) {
                        $ticketQuery->where('status', 'approved');
                    },
                    'sponsorEvents' // 👈 أضفناها هنا أيضاً لمنع رجوع الصفر
                ])
                ->withAvg('exhibitionReviews as average_rating', 'rating')
                ->latest()
                ->limit($perPage)
                ->get();
        }

        $formattedExhibitions = $exhibitions->map(function ($exhibition) {
            $endDate = $exhibition->end_date ? Carbon::parse($exhibition->end_date) : null;
            $daysLeft = $endDate ? max(0, (int) now()->diffInDays($endDate, false)) : 0;

            // معالجة الصور
            $imagesList = [];
            if ($exhibition->relationLoaded('exhibitionImages') && $exhibition->exhibitionImages->isNotEmpty()) {
                $imagesList = $exhibition->exhibitionImages->pluck('image_url')->toArray();
            } elseif (!empty($exhibition->image)) {
                $imagesList = [asset('storage/' . $exhibition->image)];
            }

            // استخراج الإحداثيات من حقل map
            $mapData = $exhibition->map ?? [];
            $latitude = isset($mapData['latitude']) ? (float) $mapData['latitude'] : (isset($mapData['lat']) ? (float) $mapData['lat'] : 0.0);
            $longitude = isset($mapData['longitude']) ? (float) $mapData['longitude'] : (isset($mapData['lng']) ? (float) $mapData['lng'] : 0.0);

            // حساب عدد الأكشاك
            $totalBooths = (int) ($exhibition->booths_count ?? $exhibition->total_booths ?? 0);

            // عدد الزائرين
            $visitorsCount = $exhibition->tickets_count > 0
                ? (int) $exhibition->tickets_count
                : (int) ($exhibition->visitors_count ?? 0);

            return [
                'id' => (int) $exhibition->id,
                'name' => (string) $exhibition->name,
                'type' => (string) ($exhibition->type ?? 'معرض'),
                'location' => (string) ($exhibition->location ?? $exhibition->city ?? ''),
                'start_date' => $exhibition->start_date ? $exhibition->start_date->format('Y-m-d') : null,
                'end_date' => $exhibition->end_date ? $exhibition->end_date->format('Y-m-d') : null,
                'description' => (string) ($exhibition->description ?? ''),
                'rating' => (float) round($exhibition->average_rating ?? 0.0, 1),
                'is_active' => true,
                'images' => $imagesList,
                'days_left' => (int) $daysLeft,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_paid' => (bool) ($exhibition->is_paid ?? false),
                'ticket_price' => (float) ($exhibition->ticket_price ?? 0.0),
                'total_booths' => $totalBooths,
                'total_events' => (int) ($exhibition->sponsor_events_count ?? 0),
                'visitors_count' => $visitorsCount,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $formattedExhibitions
        ], 200);
    }
    //===============================================================
    public function getEventsExh(Request $request, $id)
    {
        $exhibition = Exhibition::find($id);
        if (!$exhibition) {
            return response()->json([
                'status' => false,
                'message' => 'المعرض غير موجود'
            ], 404);
        }

        $perPage = (int) $request->input('per_page', 20);

        $user = auth('sanctum')->user();
        $visitorId = $user?->visitor?->id;

        // 1. جلب معرفات الحجوزات التابعة لأكشاك هذا المعرض
        $boothBookingIds = \App\Models\BoothBooking::whereHas('booth', function ($q) use ($id) {
            $q->where('exhibition_id', $id);
        })->pluck('id');

        // 2. الاستعلام عن الفعاليات مع تحميل علاقة eventTickets المعرفة
        $events = Event::whereIn('booth_booking_id', $boothBookingIds)
            ->with([
                'boothBooking.booth',
                'boothBooking.investor',
                'eventTickets' => function ($query) use ($visitorId) {
                    if ($visitorId) {
                        $query->where('visitor_id', $visitorId);
                    }
                }
            ])
            ->paginate($perPage);

        // 3. تحويل عناصر المجموعة
        $events->getCollection()->transform(function ($event) use ($exhibition, $visitorId, $id) {

            $boothBooking = $event->boothBooking;
            $booth = $boothBooking?->booth;
            $totalSeats = (int) ($event->max_participants ?? $event->total_seats ?? 0);
            $registeredCount = (int) ($event->registered_count ?? 0);
            $availableSeats = max(0, $totalSeats - $registeredCount);

            $startTime = null;
            if ($event->start_date) {
                $dateTimeString = $event->time
                    ? $event->start_date . ' ' . $event->time
                    : $event->start_date;
                $startTime = \Carbon\Carbon::parse($dateTimeString)->toIso8601String();
            }

            $endTime = null;
            if ($event->end_date) {
                $endTime = \Carbon\Carbon::parse($event->end_date)->toIso8601String();
            }

            // فحص هل الزائر مسجل في الفعالية عبر علاقة eventTickets
            $isRegistered = false;
            if ($visitorId && $event->relationLoaded('eventTickets')) {
                $isRegistered = $event->eventTickets->whereIn('status', ['pending', 'approved'])->isNotEmpty();
            }

            return [
                'id' => $event->id,
                'exhibition_id' => (int) $id,
                'name' => $event->name ?? '',
                'type' => $event->type ?? '',
                'hall' => $booth?->location ?? $event->place ?? '',
                'booth' => $booth?->number ?? $booth?->name ?? '',
                'company_name' => $boothBooking?->investor?->company_name ?? '',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'description' => $event->description ?? '',
                'image_url' => $event->video_promo_url ?? null,
                'speaker_name' => $event->speaker_name ?? '',
                'available_seats' => $availableSeats,
                'total_seats' => $totalSeats,
                'is_registered' => $isRegistered,
                'exhibition_name' => $exhibition->name ?? '',
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $events->items(),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ]
        ], 200);
    }
    //===============================================================
    public function getBoothsExh($id)
    {
        $exhibition = Exhibition::find($id);
        if (!$exhibition) {
            return response()->json([
                'status' => false,
                'message' => 'المعرض غير موجود'
            ], 404);
        }
        $booths = Booth::where('exhibition_id', $id)->get();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب أجنحة المعرض بنجاح',
            'data' => $booths
        ], 200);
    }
    //===============================================================
    public function getFloorMap($id)
    {
        // جلب المعرض مع الأكشاك التابعة له
        $exhibition = Exhibition::with('booths')->find($id);

        if (!$exhibition) {
            return response()->json([
                'status' => false,
                'message' => 'المعرض غير موجود'
            ], 404);
        }

        $mapData = (array) ($exhibition->extra_services ?? []);

        if (empty($mapData)) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات الخريطة غير متوفرة لهذا المعرض'
            ], 404);
        }

        // تجهيز بيانات الأكشاك
        $boothsData = [];
        if ($exhibition->booths) {
            foreach ($exhibition->booths as $booth) {
                $amenities = $booth->services;
                if (is_string($amenities)) {
                    $amenities = json_decode($amenities, true);
                }

                $boothsData[] = [
                    'id' => (int) $booth->id,
                    'number' => (string) $booth->number,
                    'col' => (int) ($booth->map_x ?? 0),
                    'row' => (int) ($booth->map_y ?? 0),
                    'width' => (int) ($booth->area ?? 1),
                    'depth' => (int) ($booth->map_z ?? 1),
                    'height' => 1.5,
                    'status' => ($booth->status === 'available') ? 'available' : 'booked',
                    'price' => (float) $booth->price,
                    'area' => (float) $booth->area,
                    'amenities' => is_array($amenities) ? array_values($amenities) : [],
                    'hall_id' => $booth->hall_id ?? null, // لربط الكشك بالقاعة إن وجد
                ];
            }
        }

        $rawHalls = $mapData['halls'] ?? [];

        if (empty($rawHalls)) {
            // قاعة افتراضية في حال عدم وجود تفاصيل القاعات
            $halls = [
                [
                    'id' => '1',
                    'name' => 'Main Hall',
                    'color' => 'FFFFFF',
                    'booths' => array_map(function ($b) {
                        unset($b['hall_id']);
                        return $b;
                    }, $boothsData)
                ]
            ];
        } else {
            $halls = array_map(function ($hall) use ($boothsData) {
                $hallId = (string) ($hall['id'] ?? '1');

                // فلترة الأكشاك الخاصة بهدّه القاعة إذا كانت مرتبطة بـ hall_id
                $hallBooths = array_filter($boothsData, function ($booth) use ($hallId) {
                    return !isset($booth['hall_id']) || (string) $booth['hall_id'] === $hallId;
                });

                $cleanedBooths = array_values(array_map(function ($b) {
                    unset($b['hall_id']);
                    return $b;
                }, $hallBooths));

                return [
                    'id' => $hallId,
                    'name' => (string) ($hall['name'] ?? 'Main Hall'),
                    'color' => (string) ($hall['color'] ?? 'FFFFFF'),
                    'booths' => $cleanedBooths
                ];
            }, $rawHalls);
        }

        // الاستجابة المطابقة للـ JSON المطلوب في التوثيق تماماً
        return response()->json([
            'status' => true,
            'data' => [
                'exhibition_id' => (int) $exhibition->id,
                'exhibition_name' => (string) $exhibition->name,
                'grid_width' => (int) ($mapData['grid_width'] ?? 20),
                'grid_depth' => (int) ($mapData['grid_depth'] ?? 20),
                'halls' => $halls
            ]
        ], 200);
    }

    //===============================================================
    // public function latestExhibitions()//عرض احدث المعارض
    // {
    //     $exhibitions = Exhibition::whereIn('status', ['upcoming', 'ongoing'])
    //         ->where('copy_status', 'active')
    //         ->orderBy('start_date', 'asc')
    //         ->get();

    //     $exhibitions_data = $exhibitions->map(function ($exhibition) {
    //         return [
    //             'id' => $exhibition->id,
    //             'name' => $exhibition->name,
    //             'type' => $exhibition->type,
    //             'start_date' => $exhibition->start_date,
    //             'end_date' => $exhibition->end_date,
    //             'location' => $exhibition->location,
    //             'city' => $exhibition->city,
    //             'status' => $exhibition->status,
    //             'available_booths' => $exhibition->available_booths,
    //             'total_booths' => $exhibition->total_booths,
    //             'visitors_count' => $exhibition->visitors_count,
    //             'is_favorite' => Auth::user()->favorites()
    //                 ->where('favoritable_id', $exhibition->id)
    //                 ->where('favoritable_type', Exhibition::class)
    //                 ->exists()
    //         ];

    //     });

    //     return response()->json(
    //         [
    //             'exhibitions' => $exhibitions_data
    //         ],
    //         200
    //     );
    // }
    //===============================================================
    // public function getAllExhibitions()//عرض كل المعارض
    // {
    //     $exhibitions = Exhibition::orderBy('start_date', 'asc')
    //         ->where('copy_status', 'active')
    //         ->get();

    //     $exhibitions_data = $exhibitions->map(function ($exhibition)
    //     {
    //         return [
    //             'id' => $exhibition->id,
    //             'name' => $exhibition->name,
    //             'type' => $exhibition->type,
    //             'start_date' => $exhibition->start_date,
    //             'end_date' => $exhibition->end_date,
    //             'location' => $exhibition->location,
    //             'city' => $exhibition->city,
    //             'status' => $exhibition->status,
    //             'available_booths' => $exhibition->available_booths,
    //             'total_booths' => $exhibition->total_booths,
    //             'visitors_count' => $exhibition->visitors_count,
    //             'is_favorite' => Auth::user()->favorites->where('favoritable_id', $exhibition->id)
    //                 ->where('favoritable_type', 'App\Models\Exhibition')
    //                 ->exists(),
    //             // 'booths' => $exhibition->booths,
    //         ];

    //     });

    //     return response()->json(
    //         [
    //             'exhibitions' => $exhibitions_data,
    //         ],
    //         200
    //     );
    // }
    // //===============================================================
    // public function filter(Request $request)//فلترة+بحث
    // {
    //     $query = Exhibition::query();

    //     if ($request->has('latest') && $request->latest != '') {

    //     }

    //     // بحث بالاسم
    //     if ($request->has('search') && $request->search != '') {
    //         $query->where('name', 'LIKE', '%' . $request->search . '%');
    //     }

    //     // فلترة حسب الحالة
    //     if ($request->has('status') && in_array($request->status, ['far', 'upcoming', 'ongoing', 'finished'])) {
    //         $query->where('status', $request->status);
    //     }

    //     // فلترة حسب المدينة
    //     if ($request->has('city') && $request->city != '') {
    //         $query->where('city', $request->city);
    //     }

    //     // فلترة حسب القطاع
    //     if ($request->has('sector') && $request->sector != '') {
    //         $query->whereJsonContains('sectors', $request->sector);
    //     }

    //     $exhibitions = $query->orderBy('start_date', 'asc')->get();

    //     return response()->json(
    //         [
    //             'exhibitions' => $exhibitions
    //         ],
    //         200
    //     );
    // }



}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExhibitionRequest;
use App\Http\Requests\UpdateExhibitionRequest;
use App\Http\Resources\ExhibitionResource;
use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\Copy;
use App\Models\Event;
use App\Models\Exhibition;
use App\Models\ExhibitionImage;
use App\Models\Favorite;
use App\Models\PortalLink;
use App\Models\SponserEventTicket;
use App\Models\SponsorEvent;
use App\Models\SponsorshipBooking;
use App\Models\StaffMember;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;


class ExhibitionController extends Controller
{
    //===============================================================
    //**************************----o----****************************
    //===============================================================
    public function store(StoreExhibitionRequest $request)// اضافة معرض
    {
        $user = Auth::user();
        $organizer = $user?->organizer;

        if (!$organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer profile not found.'
            ], 403);
        }

        $alreadyExists = Exhibition::where('organizer_id', $organizer->id)->exists();
        if ($alreadyExists) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer can only manage one exhibition.'
            ], 409);
        }

        $validate_data = $request->validated();

        if (isset($validate_data['status'])) {
            $validate_data['status'] = match ($validate_data['status']) {
                'draft' => 'far',
                'active' => 'upcoming',
                'archived' => 'finished',
                default => $validate_data['status'],
            };
        }

        $validate_data['organizer_id'] = $organizer->id;

        $exhibition = Exhibition::create($validate_data);

        if ($exhibition->start_date && $exhibition->end_date) {
            $exhibition->copies()->create([
                'year' => Carbon::parse($exhibition->start_date)->year,
                'start_date' => $exhibition->start_date,
                'end_date' => $exhibition->end_date,
                'copy_status' => 'active',
                'announced' => true,
                'total_booths' => $exhibition->total_booths ?? 0,
                'available_booths' => $exhibition->total_booths ?? 0,
            ]);
        }

        app(NotificationService::class)->forExhibition(
            $exhibition, 'تم إنشاء معرض جديد', 'تم إنشاء معرض جديد وإتاحته للإدارة.', 'exhibition', 'admin.company', [], '/exhibitions', ['admin.map', 'org.map']
        );

        // return response()->json([
        //     'message' => 'Exhibition created successfully',
        //     'exhibition' => $exhibition
        // ], 201);

        return new ExhibitionResource($exhibition);
    }
    //===============================================================
    public function update(StoreExhibitionRequest $request, $exhibition_id)//تعديل معرض
    {
        $user = Auth::user();
        $organizer = $user?->organizer;
        $portal = null;

        if ($organizer) {
            $exhibition = Exhibition::where('organizer_id', $organizer->id)
                ->whereKey($exhibition_id)
                ->first();
        } else {
            $portalQuery = PortalLink::query()
                ->where('staff_id', $user?->staff?->id)
                ->where('exhibition_id', $exhibition_id)
                ->where('role', 'organizational')
                ->where('active', true);
            $portalToken = $request->header('X-Portal-Token');
            if ($portalToken) $portalQuery->where('token', $portalToken);
            $portal = $portalQuery->first();
            $exhibition = $portal ? Exhibition::find($exhibition_id) : null;
        }

        if (!$exhibition)
        {

            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this exhibition because it does not belong to you.'
            ], 403);
        }

        $data = $request->validated();
        if ($portal) {
            abort_unless(in_array('org.map', $portal->permissions ?? [], true), 403);
            $data = array_intersect_key($data, ['map_built' => true]);
        }
        $exhibition->update($data);

        app(NotificationService::class)->forExhibition(
            $exhibition, 'تم تعديل بيانات المعرض', 'تم تعديل بيانات المعرض أو إعداداته.', 'exhibition', 'admin.company', [], '/exhibitions', ['admin.map', 'org.map']
        );

        // return response()->json([
        //     'message' => 'Exhibition updated successfully',
        //     'exhibition' => $exhibition
        // ], 200);
        return new ExhibitionResource($exhibition);
    }
    //===============================================================
    public function index()
    {
        $exhibitions = Exhibition::with('copies')->get();

        return ExhibitionResource::collection($exhibitions);
    }
    //===============================================================
    public function organizerExhibition()
    {
        $user = Auth::user();
        $organizer = $user?->organizer;

        if (!$organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer profile not found.',
                'data' => null,
            ], 403);
        }

        $exhibition = Exhibition::where('organizer_id', $organizer->id)
            ->with('copies')
            ->first();

        if (!$exhibition) {
            return response()->json([
                'success' => false,
                'message' => 'No exhibition found',
                'data' => null,
            ], 200);
        }

        return new ExhibitionResource($exhibition);
    }
    //===============================================================
    public function showExhibition($exhibition_id)
    {
        $exhibition = Exhibition::with('copies')->findOrFail($exhibition_id);
        return new ExhibitionResource($exhibition);
    }
    //===============================================================
    public function BuiltMap($exhibition_id)
    {
        $exhibition = Exhibition::find($exhibition_id);

        if (!$exhibition) {
            return response()->json(['message' => 'Exhibition not found'], 404);
        }

        $exhibition->update([
            'map_built' => true
        ]);

        app(NotificationService::class)->forExhibition(
            $exhibition, 'تم تجهيز خريطة المعرض', 'تم تجهيز خريطة المعرض للاستخدام.', 'map', 'org.map', [], '/map', ['admin.map']
        );

        // return response()->json([
        //     'message' => 'Map Built Exhibition updated successfully',
        // ], 200);
        return new ExhibitionResource($exhibition);
    }
    //===============================================================
    public function archive(Request $request, $exhibition_id)//ارشفة معرض
    {
        $organizer = Auth::user()->organizer;
        $exhibition = Exhibition::where('organizer_id', $organizer->id)
            ->where('id', $exhibition_id)
            ->first();

        if (!$exhibition)
        {

            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this exhibition because it does not belong to you.'
            ], 403);
        }


        $editionId = $request->edition_id;

        $copy = Copy::where('exhibition_id', $exhibition_id)
            ->where('id', $editionId)
            ->first();

        if (!$copy)
        {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this copy because it does not belong to your exhibition.'
            ], 403);
        }

        $copy->update([
            'copy_status' => 'archived'
        ]);

        app(NotificationService::class)->forExhibition(
            $exhibition, 'تمت أرشفة إصدار المعرض', 'تمت أرشفة إصدار من إصدارات المعرض.', 'exhibition', 'admin.company', [], '/exhibitions', ['admin.reports']
        );

        return response()->json([
            'success' => true,
            'message' => 'Exhibition archived successfully'
        ], 200);
    }
    //===============================================================
    public function destroy($exhibition_id)//حذف معرض
    {
        $organizer = Auth::user()->organizer;
        $exhibition = Exhibition::where('organizer_id', $organizer->id)
            ->where('id', $exhibition_id)
            ->first();

        if (!$exhibition)
        {

            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this exhibition because it does not belong to you.'
            ], 403);
        }

        app(NotificationService::class)->forExhibition(
            $exhibition, 'تم حذف المعرض', 'تم حذف المعرض من المنصة.', 'exhibition', 'admin.company', [], '/exhibitions', ['admin.reports']
        );
        $exhibition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exhibition deleted successfully'
        ], 200);
    }
    //===============================================================
    //===============================================================


    //===============================================================
    //************************---i---********************************
    //===============================================================
    public function getAllExhibitions(Request $request)//✅
    {
        $page     = $request->query('page', 1);
        $per_page = $request->query('per_page', 15);

        $search = $request->query('search');
        $city   = $request->query('city');
        $sector = $request->query('sector');
        $status = $request->query('status');

        $query = Exhibition::query()
            ->join('copies', 'copies.exhibition_id', '=', 'exhibitions.id')
            ->where('copies.copy_status', 'active')
            ->orderBy('exhibitions.start_date', 'asc')
            ->with([
                'sponsorEvents',
                'publishedMap',
                'booths',
                'copies'
            ])
            ->select('exhibitions.*');

        // search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                ->orWhere('city', 'LIKE', "%$search%");
            });
        }

        // city
        if ($city) {
            $query->where('city', $city);
        }

        // sector
        if ($sector) {
            $query->whereJsonContains('sectors', $sector);
        }

        // status
        if ($status) {
            $statusMap = [
                'upcoming' => 'upcoming',
                'active'   => 'ongoing',
                'ended'    => 'finished'
            ];

            if (isset($statusMap[$status])) {
                $query->where('status', $statusMap[$status]);
            }
        }

        // paginate
        $exhibitions = $query->paginate($per_page, ['*'], 'page', $page);

        $user = auth('sanctum')->user();

        $exhibitions_data = $exhibitions->map(function ($exhibition) use ($user) {
            return [
                'id'               => $exhibition->id,
                'name'             => $exhibition->name,
                'description'      => $exhibition->description,
                'images'           => $exhibition->images ?? [],
                'services'         => $exhibition->extra_services
                                        ? collect($exhibition->extra_services)->pluck('name')->toArray()
                                        : [],
                'mapJson'          => $exhibition->publishedMap?->map_json, // ← الخريطة المنشورة
                'sponsor_events'   => $exhibition->sponsorEvents,  // ← الفعاليات
                'start_date'       => Carbon::parse($exhibition->start_date)->format('Y-m-d'),
                'end_date'         => Carbon::parse($exhibition->end_date)->format('Y-m-d'),
                'location'         => $exhibition->location,
                'city'             => $exhibition->city,
                'status'           => $exhibition->status,
                'available_booths' => $this->investorAvailableBooths($exhibition),
                'sectors'          => $exhibition->sectors ?? [],
                'is_favorite'      => $user ? $user->favorites()
                    ->where('favoritable_id', $exhibition->id)
                    ->where('favoritable_type', Exhibition::class)
                    ->exists() : false,
            ];
        });

        return response()->json([
            'data' => $exhibitions_data,
            'pagination' => [
                'current_page' => $exhibitions->currentPage(),
                'per_page'     => $exhibitions->perPage(),
                'total'        => $exhibitions->total(),
                'last_page'    => $exhibitions->lastPage(),
            ]
        ], 200);
    }

    private function investorAvailableBooths(Exhibition $exhibition): int
    {
        $available = $exhibition->booths
            ->whereIn('status_inv', ['available', null])
            ->whereIn('status', ['available', null])
            ->count();

        if ($available > 0) {
            return $available;
        }

        return (int) ($exhibition->copies
            ->where('copy_status', 'active')
            ->sortByDesc('year')
            ->first()
            ?->available_booths ?? 0);
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

        $map_data = $exhibition->publishedMap;

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
            'available_booths' => $user?->role === 'investor'
                ? $this->investorAvailableBooths($exhibition)
                : $exhibition->available_booths,
            'sectors' => $exhibition->sectors ?? [],
            'is_favorite' => $is_favorite,
            'map_data' => $user?->role === 'investor'
                ? $map_data?->map_json
                : $map_data,
            'sponsor_events' => $sponsor_events,
        ], 200);
    }
    //===============================================================



    //===============================================================
    //===============================================================
    // public function AllExhibitions()
    // {
    //     $exhibitions = Exhibition::all();
    //     $data = $exhibitions->map(function ($exhibition)
    //     {
    //         return
    //         [
    //             'id' => $exhibition->id,
    //             'organizer_id' => $exhibition->organizer_id,
    //             'name' => $exhibition->name,
    //             'location' => $exhibition->location,
    //             'start_date' => Carbon::parse($exhibition->start_date)->format('Y-m-d'),
    //             'end_date' => Carbon::parse($exhibition->end_date)->format('Y-m-d'),
    //             'working_hours' => $exhibition->working_hours,
    //             'total_booths' => $exhibition->total_booths,
    //             'booked_booths' => ($exhibition->total_booths) - ($exhibition->available_booths),
    //             'type' => $exhibition->type,
    //             'status' => $exhibition->status,
    //             'map_built' => $exhibition->map_built,
    //         ];
    //     });

    //     return response()->json([
    //         'data' => $data
    //     ], 200);
    // }
    // //===============================================================
    // public function getMyExhibition()//عرض المعرض الخاص بي
    // {
    //     $organizer = Auth::user()->organizer;

    //     $exhibition = Exhibition::where('organizer_id', $organizer->id)
    //         ->with('booths');

    //     return response()->json([
    //         'exhibition' => $exhibition
    //     ], 200);
    // }
    // //===============================================================
    // public function getOneExhibition($exhibition_id)
    // {
    //     $exhibition = Exhibition::with([
    //         'booths',
    //         'sponsorEvents'
    //     ])->find($exhibition_id);

    //     if (!$exhibition)
    //     {
    //         return response()->json(['message' => 'Exhibition not found'], 404);
    //     }
    //     //____________________________________________
    //     //announced(copy_status)
    //     if($exhibition->copy_status == 'active')
    //     {
    //         $announced = true;
    //     }
    //     else
    //     {
    //         $announced = false;
    //     }
    //     //____________________________________________
    //     //pending_requests
    //     $booths = $exhibition->booths;
    //     $booth_ids = $booths->pluck('id');
    //     $pending_requests = BoothBooking::whereIn('booth_id',$booth_ids)
    //             ->where('status','pending')
    //             ->count();
    //     //____________________________________________
    //     //expected_visitors
    //     $visitors = User::where('role','visitor')->get();
    //     $visitors_ids = $visitors->pluck('id');
    //     $expected_visitors = Favorite::where('favoritable_id', $exhibition_id)
    //                 ->where('favoritable_type', Exhibition::class)
    //                 ->whereIn('user_id', $visitors_ids)
    //                 ->count();
    //     //____________________________________________
    //     //turnout_percent(عدد المشاركة)
    //     $turnout_percent = 0;
    //     //____________________________________________
    //     //expected_turnout_percent
    //     $expected_turnout_percent = 0;
    //     //____________________________________________
    //     //revenue(الدخل)
    //     $booth_bokings_revenue = Booth::where('exhibition_id', $exhibition_id)->pluck('price');
    //     $tickets_revenue = Ticket::where('exhibition_id', $exhibition_id)->pluck('amount');
    //     $sponsorships_bookings_revenue = SponsorshipBooking::where('exhibition_id', $exhibition_id)->pluck('total_price');

    //     $sponsor_events = SponsorEvent::where('exhibition_id', $exhibition_id)->get();
    //     $sponsor_events_ids = $$sponsor_events->pluck('id');
    //     $sponsorEvent_tickets_revenue = SponserEventTicket::whereIn('sponsor_event_id', $sponsor_events_ids)->pluck('amount');

    //     $revenue = $booth_bokings_revenue +
    //             $tickets_revenue +
    //             $sponsorships_bookings_revenue +
    //             $sponsorEvent_tickets_revenue;
    //     //____________________________________________
    //     //expected_revenue
    //     $min_booth_price = min($booth_bokings_revenue);

    //     $investors = User::where('role','investor')->get();
    //     $investors_ids = $investors->pluck('id');
    //     $expected_investors = Favorite::where('favoritable_id', $exhibition_id)
    //                 ->where('favoritable_type', Exhibition::class)
    //                 ->whereIn('user_id', $investors_ids)
    //                 ->count();

    //     $expected_revenue = ($tickets_revenue * $expected_visitors) + ($min_booth_price * $expected_investors) + $revenue ;
    //     //____________________________________________
    //     //staff_count
    //     $staff_count = StaffMember::where('exhibition_id', $exhibition_id)->count();
    //     //____________________________________________
    //     //sponsorship_percent(نسبة الرعاية)
    //     $sponsors = $exhibition->sponsors;
    //     $sponsors_ids = $sponsors->pluck('id');
    //     $sponsorship_percent = BoothBooking::whereIn('sponsor_id',$sponsors_ids)
    //             ->where('status','approved')
    //             ->count();
    //     //____________________________________________
    //     //final_booked_booths()
    //     $final_booked_booths = BoothBooking::whereIn('booth_id',$booth_ids)
    //             ->where('status','finished')
    //             ->count();
    //     //____________________________________________
    //     $exhibition_data =
    //     [
    //         'id' => $exhibition->id,
    //         'organizer_id' => $exhibition->organizer_id,
    //         'label' => $exhibition->current_copy,
    //         'start_date' => Carbon::parse($exhibition->start_date)->format('Y-m-d'),
    //         'end_date' => Carbon::parse($exhibition->end_date)->format('Y-m-d'),
    //         'announced' => $announced,
    //         'total_booths' => $exhibition->total_booths,
    //         'booked_booths' => ($exhibition->total_booths) - ($exhibition->available_booths),
    //         'available_booths' => $exhibition->available_booths,
    //         'pending_requests' => $pending_requests,
    //         'visitor_count' => $exhibition->visitor_count,
    //         'expected_visitors' => $expected_visitors,
    //         'turnout_percent' => $turnout_percent,//❌
    //         'expected_turnout_percent' => $expected_turnout_percent,//❌
    //         'revenue' => $revenue,
    //         'expected_revenue' => $expected_revenue,
    //         'staff_count' => $staff_count,
    //         'sponsorship_percent' => $sponsorship_percent,//⛔
    //         'final_booked_booths' => $final_booked_booths,
    //     ];

    //     return response()->json([
    //         'exhibition' => $exhibition_data
    //     ], 200);
    // }
    // //===============================================================
    // //===============================================================
    // public function StoreImages(Request $request, $exhibition_id)//اضافة صورة للمعرض
    // {
    //     $request->validate([
    //         'image' => 'required',
    //         'image.*' => 'image|mimes:jpg,jpeg,png|max:2048'
    //     ]);
    //     $images = [];
    //     if ($request->hasFile('image')) {
    //         foreach ($request->file('image') as $img) {
    //             $path = $img->store('exhibition_images', 'public');

    //             $images[] = ExhibitionImage::create([
    //                 'exhibition_id' => $exhibition_id,
    //                 'image' => $path
    //             ]);
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'Images Exhibition stored successfully',
    //         'images' => $images
    //     ], 201);
    // }
    //===============================================================
    //===============================================================
    //===============================================================
    // public function getMap($exhibition_id)//عرض خريطة معرض
    // {
    //     $organizer = Auth::user()->organizer;

    //     $exhibition = Exhibition::where('organizer_id', $organizer->id)
    //         ->findOrFail($exhibition_id);
    //     $map = json_decode($exhibition->map, true);

    //     return response()->json([
    //         'map' => $map,
    //     ], 200);

    // }
    // //===============================================================
    // //===============================================================
    // public function ongoing()//الجارية
    // {
    //     $exhibitions = Exhibition::where('status', 'ongoing')
    //         ->orderBy('start_date', 'asc')
    //         ->get();

    //     return response()->json([
    //         'exhibitions' => $exhibitions
    //     ], 200);
    // }
    // //===============================================================
    // public function finished()//المنتهية
    // {
    //     $exhibitions = Exhibition::where('status', 'finished')
    //         ->orderBy('end_date', 'desc')
    //         ->get();

    //     return response()->json([
    //         'exhibitions' => $exhibitions
    //     ], 200);
    // }
    // //===============================================================
    // public function upcoming()//القادمة
    // {
    //     $exhibitions = Exhibition::where('status', 'upcoming')
    //         ->orderBy('start_date', 'asc')
    //         ->get();

    //     return response()->json([
    //         'exhibitions' => $exhibitions
    //     ], 200);
    // }

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
        $query = Exhibition::with(['exhibitionImages','publishedMap'])
            ->withCount([
                'booths',
                'tickets' => function ($ticketQuery) {
                    $ticketQuery->where('status', 'approved');
                },
                'sponsorEvents'
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

        if ($exhibitions->isEmpty()) {
            $exhibitions = Exhibition::with(['exhibitionImages'])
                ->withCount([
                    'booths',
                    'tickets' => function ($ticketQuery) {
                        $ticketQuery->where('status', 'approved');
                    },
                    'sponsorEvents'
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

        $boothBookingIds = \App\Models\BoothBooking::whereHas('booth', function ($q) use ($id) {
            $q->where('exhibition_id', $id);
        })->pluck('id');
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

}

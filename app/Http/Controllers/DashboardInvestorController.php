<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\Copy;
use App\Models\Event;
use App\Models\Exhibition;
use App\Models\Favorite;
use App\Models\Investor;
use App\Models\SponsorEvent;
use App\Models\SponsorshipBooking;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardInvestorController extends Controller
{
    public function featuredExhibitions(Request $request)//✅
    {
        $investor = Auth::user()->investor;

        $page     = $request->query('page', 1);
        $per_page = $request->query('per_page', 5);

        $query = Exhibition::query()
        ->join('copies', 'copies.exhibition_id', '=', 'exhibitions.id')
        ->where('copies.copy_status', 'active') // النسخ النشطة فقط
        ->where('exhibitions.location', $investor->location)
        ->where('exhibitions.type', $investor->activity_type)
        ->whereIn('exhibitions.status', ['upcoming', 'ongoing'])
        ->where(function ($q) {
            $q->whereHas('booths', function ($booths) {
                $booths->where(function ($status) {
                    $status->whereIn('status_inv', ['available', null])
                        ->whereIn('status', ['available', null]);
                });
            })->orWhereHas('copies', function ($copies) {
                $copies->where('copy_status', 'active')
                    ->where('available_booths', '>', 0);
            });
        })
        ->orderBy('exhibitions.start_date', 'asc')
        ->select('exhibitions.*') // مهم حتى يرجع موديل Exhibition كامل
        ->paginate($per_page, ['*'], 'page', $page);

        $data = $query->map(function ($ex)
        {
            return
            [
                'id'               => $ex->id,
                'name'             => $ex->name,
                'images'           => $ex->exhibitionImages ?? [],
                'start_date'       => Carbon::parse($ex->start_date)->format('Y-m-d'),
                'end_date'         => Carbon::parse($ex->end_date)->format('Y-m-d'),
                'location'         => $ex->location,
                'city'             => $ex->city,
                'status'           => $ex->status,
                'available_booths' => $ex->booths()
                    ->whereIn('status_inv', ['available', null])
                    ->whereIn('status', ['available', null])
                    ->count() ?: (int) ($ex->copies()
                        ->where('copy_status', 'active')
                        ->value('available_booths') ?? 0),
                'sectors'          => $ex->sectors ?? [],
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' =>
            [
                'current_page' => $query->currentPage(),
                'last_page'    => $query->lastPage(),
                'per_page'     => $query->perPage(),
                'total'        => $query->total(),
            ]
        ], 200);

    }
    //=====================================================================
    public function featuredSponsorEvents(Request $request)//✅
    {
        $investor = Auth::user()->investor;

        $page     = $request->query('page', 1);
        $per_page = $request->query('per_page', 5);

        // 1) جلب المعارض المميزة للمستثمر بنفس شروط لوحة المعارض.
        // لا نعتمد على exhibitions.available_booths لأنها قديمة في بعض النسخ؛
        // التوفر الفعلي يحسب من الأجنحة والنسخ النشطة.
        $featuredExhibitionsIds = Exhibition::query()
            ->join('copies', 'copies.exhibition_id', '=', 'exhibitions.id')
            ->where('copies.copy_status', 'active')
            ->where('exhibitions.location', $investor->location)
            ->where('exhibitions.type', $investor->activity_type)
            ->whereIn('exhibitions.status', ['far', 'upcoming', 'ongoing'])
            ->where(function ($query) {
                $query->whereHas('copies', function ($copies) {
                    $copies->where('copy_status', 'active');
                });
            })
            ->pluck('exhibitions.id');

        // 2) جلب الفعاليات التابعة لهذه المعارض فقط
        $events = SponsorEvent::query()
            ->with(['exhibition.exhibitionImages', 'sponsorEventImages', 'programs'])
            ->whereIn('exhibition_id', $featuredExhibitionsIds)
            ->where('copy_status', 'published')
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->orderBy('start_time', 'asc')
            ->paginate($per_page, ['*'], 'page', $page);

        // 3) تجهيز الريسبونس
        $data = $events->map(function ($ev) {
            $ex = $ev->exhibition;

            return [
                'id'                    => $ev->id,
                'name'                  => $ev->name,
                'type'                  => $ev->type,
                'exhibition_id'         => $ev->exhibition_id,
                'exhibition_name'       => $ex->name ?? null,
                'exhibition_image_url'  => $this->publicImageUrl($ex->exhibitionImages->pluck('image')->first()),
                'date'                  => Carbon::parse($ev->start_time)->format('Y-m-d'),
                'start_time'            => Carbon::parse($ev->start_time)->format('H:i'),
                'end_time'              => Carbon::parse($ev->end_time)->format('H:i'),
                'place'                 => $ev->place,
                'listing_days'          => $ev->duration_days,
                'description'           => $ev->description,
                'capacity'              => $ev->max_participants,
                'registered_count'      => $ev->registered_count,
                'scanned_count'         => $ev->scanned_count,
                'ticket_type'           => $ev->ticket_type,
                'ticket_price'          => $ev->ticket_price,
                'status'                => $ev->status,
                'copy_status'           => $ev->copy_status,
                'publish_date'          => $ev->publish_date,
                'images'                => $ev->sponsorEventImages
                    ->map(fn ($image) => [
                        'id' => $image->id,
                        'url' => $this->publicImageUrl($image->image),
                        'caption' => $image->caption,
                    ])->values()->all(),
                'activities'            => $ev->programs->map(fn ($program) => [
                    'id' => $program->id,
                    'title' => $program->title,
                    'start_time' => $program->start_time,
                    'end_time' => $program->end_time,
                    'provider_name' => $program->provider_name,
                    'provider_contact' => $program->provider_contact,
                ])->values()->all(),
                'duration_days'         => $ev->duration_days,
                'daily_price'           => $ev->daily_price,
                'duration_options'      => $this->buildDurationOptions($ev), // ← هنا التابع المطلوب
                'durationOptions'       => $this->buildDurationOptions($ev),
                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $ev->id)
                    ->where('favoritable_type', SponsorEvent::class)
                    ->exists()
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page'    => $events->lastPage(),
                'per_page'     => $events->perPage(),
                'total'        => $events->total(),
            ]
        ], 200);
    }
    //=====================================================================
    private function buildDurationOptions($event)//↕️
    {
        if (is_string($event->duration_options)) {
            $decoded = json_decode($event->duration_options, true);
            $event->duration_options = is_array($decoded) ? $decoded : [];
        }

        if (is_array($event->duration_options) && count($event->duration_options) > 0) {
            return array_map(function ($option) {
                return [
                    'label' => ((int) $option['days'] === 1)
                        ? 'يوم واحد'
                        : ((int) $option['days'] . ' أيام'),
                    'days' => (int) $option['days'],
                    'start_date' => $option['start_date'] ?? null,
                    'end_date' => $option['end_date'] ?? null,
                    'price' => (float) $option['price'],
                ];
            }, $event->duration_options);
        }

        if ($event->daily_price === null) {
            return [];
        }

        $options = [];

        $daily = $event->daily_price;
        $totalDays = $event->duration_days;

        // إذا الفعالية يوم واحد فقط
        if ($totalDays == 1) {
            $options[] = [
                'label' => 'يوم واحد',
                'days'  => 1,
                'price' => $daily,
            ];
            return $options;
        }

        // إذا أكثر من يوم واحد → نبني الخيارات حسب عدد الأيام
        // 1) خيار اليوم الواحد
        $options[] = [
            'label' => 'يوم واحد',
            'days'  => 1,
            'price' => $daily,
        ];

        // 2) إذا يومين → نضيف يومين فقط
        if ($totalDays == 2) {
            $options[] = [
                'label' => 'يومان',
                'days'  => 2,
                'price' => $daily * 2,
            ];
            return $options;
        }

        // 3) إذا 3 أيام أو أكثر → نضيف يومان
        $options[] = [
            'label' => 'يومان',
            'days'  => 2,
            'price' => $daily * 2,
        ];

        // 4) كامل الفعالية
        $options[] = [
            'label' => "$totalDays أيام",
            'days'  => $totalDays,
            'price' => $daily * $totalDays,
        ];

        return $options;
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (!$path) return null;
        return str_starts_with($path, 'data:') || filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : asset('storage/' . ltrim($path, '/'));
    }
    //=====================================================================
    private function getDateRanges($period)//↕️
    {
        switch ($period)
        {
            case 'day':
                $current_start = now()->startOfDay();
                $current_end   = now()->endOfDay();

                $previous_start = now()->subDay()->startOfDay();
                $previous_end   = now()->subDay()->endOfDay();
                break;

            case 'week':
                $current_start = now()->startOfWeek();
                $current_end   = now()->endOfWeek();

                $previous_start = now()->subWeek()->startOfWeek();
                $previous_end   = now()->subWeek()->endOfWeek();
                break;

            case 'month':
                $current_start = now()->startOfMonth();
                $current_end   = now()->endOfMonth();

                $previous_start = now()->subMonth()->startOfMonth();
                $previous_end   = now()->subMonth()->endOfMonth();
                break;

            case 'quarter':
                $current_start = now()->subMonths(2)->startOfMonth();
                $current_end   = now()->endOfMonth();

                $previous_start = now()->subMonths(5)->startOfMonth();
                $previous_end   = now()->subMonths(3)->endOfMonth();
                break;

            case 'year':
                $current_start = now()->startOfYear();
                $current_end   = now()->endOfYear();

                $previous_start = now()->subYear()->startOfYear();
                $previous_end   = now()->subYear()->endOfYear();
                break;

            default:
                $current_start = now()->startOfMonth();
                $current_end   = now()->endOfMonth();

                $previous_start = now()->subMonth()->startOfMonth();
                $previous_end   = now()->subMonth()->endOfMonth();
        }

        return [
            $current_start,
            $current_end,
            $previous_start,
            $previous_end
        ];
    }
    //=====================================================================
    public function dashboard(Request $request)//✅
    {
        $period = $request->query('period', 'month');
        $investor_id = Auth::user()->investor->id;

        [$current_start, $current_end, $previous_start, $previous_end] = $this->getDateRanges($period);

        $current_bookings = BoothBooking::where('investor_id', $investor_id)
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$current_start, $current_end])
            ->get();

        $current_total_bookings = $current_bookings->whereIn('status', ['approved', 'finished'])->count();
        $current_active_booths  = $current_bookings->where('status', 'approved')->count();


        $previous_bookings = BoothBooking::where('investor_id', $investor_id)
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$previous_start, $previous_end])
            ->get();

        $previous_total_bookings = $previous_bookings->whereIn('status', ['approved', 'finished'])->count();
        $previous_active_booths  = $previous_bookings->where('status', 'approved')->count();

        //-----Events------------------------
        $all_booking_ids = BoothBooking::where('investor_id', $investor_id)->pluck('id');


        $current_events = Event::whereIn('booth_booking_id', $all_booking_ids)
            ->whereBetween('created_at', [$current_start, $current_end])
            ->get();

        $current_published_events = $current_events->count();
        $current_engagement = $current_events->sum('scanned_count') + $current_events->sum('registered_count');


        $previous_events = Event::whereIn('booth_booking_id', $all_booking_ids)
            ->whereBetween('created_at', [$previous_start, $previous_end])
            ->get();

        $previous_published_events = $previous_events->count();
        $previous_engagement = $previous_events->sum('scanned_count') + $previous_events->sum('registered_count');

        //----------------growthRate---------------------
        $growth_total_bookings   = $this->growthRate($current_total_bookings, $previous_total_bookings);
        $growth_active_booths    = $this->growthRate($current_active_booths, $previous_active_booths);
        $growth_published_events = $this->growthRate($current_published_events, $previous_published_events);
        $growth_engagement       = $this->growthRate($current_engagement, $previous_engagement);
        //------------------------------------------

        return response()->json([
            'total_bookings' => $current_total_bookings,
            'active_booths' => $current_active_booths,
            'published_events' => $current_published_events,
            'total_engagement' => $current_engagement,
            'growth' => [
                'total_bookings' => $growth_total_bookings,
                'active_booths' => $growth_active_booths,
                'published_events' => $growth_published_events,
                'total_engagement' => $growth_engagement,
            ],
            'period' => $period,
        ], 200);

        // return response()->json([
        //     'summary' => [
        //         'total_bookings' =>
        //         [
        //             'value'  => $current_total_bookings,
        //             'growth' => $growth_total_bookings
        //         ],
        //         'active_booths' =>
        //         [
        //             'value'  => $current_active_booths,
        //             'growth' => $growth_active_booths
        //         ],
        //         'published_events' =>
        //         [
        //             'value'  => $current_published_events,
        //             'growth' => $growth_published_events
        //         ],
        //         'total_engagement' =>
        //         [
        //             'value'  => $current_engagement,
        //             'growth' => $growth_engagement
        //         ],
        //     ],

        //     'period' => $period,
        //     'current_range' =>
        //     [
        //         'start' => $current_start->format('Y-m-d'),
        //         'end'   => $current_end->format('Y-m-d'),
        //     ],
        //     'previous_range' =>
        //     [
        //         'start' => $previous_start->format('Y-m-d'),
        //         'end'   => $previous_end->format('Y-m-d'),
        //     ]
        // ], 200);
    }
    //=====================================================================
    private function growthRate($current, $previous)//↕️
    {
        if ($previous == 0)
        {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
    //=====================================================================
    public function latestExhibitions()//✅
    {
        $exhibitions = Exhibition::query()
        ->join('copies', 'copies.exhibition_id', '=', 'exhibitions.id')
        ->where('copies.copy_status', 'active') // النسخ النشطة فقط
        ->whereIn('exhibitions.status', ['upcoming', 'ongoing'])
        ->orderBy('exhibitions.start_date', 'asc')
        ->select('exhibitions.*')
        ->get() ;// مهم حتى يرجع موديل Exhibition كامل


        $exhibitions_data = $exhibitions->map(function ($exhibition)
        {
            return
            [
                'id' => $exhibition->id,
                'name' => $exhibition->name,
                'description' => $exhibition->description,
                'images' => $exhibition->exhibitionImages ?? [],
                'services' => $exhibition->extra_services
                    ? collect($exhibition->extra_services, true)->pluck('name')->toArray()
                    : [],
                'start_date' => Carbon::parse($exhibition->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($exhibition->end_date)->format('Y-m-d'),
                'location' => $exhibition->location,
                'city' => $exhibition->city,
                'status' => $exhibition->status,
                'available_booths' => $exhibition->available_booths,
                'sectors' => $exhibition->sectors ?? [],
                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $exhibition->id)
                    ->where('favoritable_type', Exhibition::class)
                    ->exists()
            ];
        });

        return response()->json($exhibitions_data, 200);
    }
    //=====================================================================
    //=====================================================================
    // // 1) جلب النسخ النشطة
        // $copies = Copy::where('copy_status', 'active')->get();

        // // 2) تجميع المعارض التابعة لكل نسخة وتنفيذ الشروط
        // $exhibitionsCollection = collect();

        // foreach ($copies as $copy)
        // {

        //     $ex = $copy->exhibition;

        //     if (!$ex) continue; // احتياط لو نسخة بدون معرض

        //     // تطبيق الشروط
        //     if (
        //         $ex->location === $investor->location &&
        //         $ex->type === $investor->activity_type &&
        //         in_array($ex->status, ['upcoming', 'ongoing']) &&
        //         $ex->available_booths > 0
        //     )
        //     {
        //         $exhibitionsCollection->push($ex);
        //     }
        // }

        // // 3) ترتيب حسب تاريخ البداية
        // $sorted = $exhibitionsCollection->sortBy('start_date')->values();

        // // 4) عمل Pagination يدوي على الـ Collection
        // $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
        //     $sorted->forPage($page, $per_page),
        //     $sorted->count(),
        //     $per_page,
        //     $page,
        //     ['path' => $request->url(), 'query' => $request->query()]
        // );

        // // 5) تجهيز البيانات
        // $data = $paginated->map(function ($ex)
        // {
        //     return
        //     [
        //         'id'               => $ex->id,
        //         'name'             => $ex->name,
        //         'images'           => $ex->exhibitionImages ?? [],
        //         'start_date'       => Carbon::parse($ex->start_date)->format('Y-m-d'),
        //         'end_date'         => Carbon::parse($ex->end_date)->format('Y-m-d'),
        //         'location'         => $ex->location,
        //         'city'             => $ex->city,
        //         'status'           => $ex->status,
        //         'available_booths' => $ex->available_booths,
        //         'sectors'          => $ex->sectors ?? [],
        //     ];
        // });

        // return response()->json([
        //     'data' => $data,
        //     'meta' => [
        //         'current_page' => $paginated->currentPage(),
        //         'last_page'    => $paginated->lastPage(),
        //         'per_page'     => $paginated->perPage(),
        //         'total'        => $paginated->total(),
        //     ]
        // ], 200);




















    // private function getDateRanges($period)
    // {
    //     // switch ($period)
    //     // {
    //     //     case 'this_month':
    //     //         $current_start = now()->startOfMonth();
    //     //         $current_end   = now()->endOfMonth();

    //     //         $previous_start = now()->subMonth()->startOfMonth();
    //     //         $previous_end   = now()->subMonth()->endOfMonth();
    //     //         break;

    //     //     case 'last_3_months':
    //     //         $current_start = now()->subMonths(3)->startOfDay();
    //     //         $current_end   = now()->endOfDay();

    //     //         $previous_start = now()->subMonths(6)->startOfDay();
    //     //         $previous_end   = now()->subMonths(3)->endOfDay();
    //     //         break;

    //     //     case 'this_year':
    //     //         $current_start = now()->startOfYear();
    //     //         $current_end   = now()->endOfYear();

    //     //         $previous_start = now()->subYear()->startOfYear();
    //     //         $previous_end   = now()->subYear()->endOfYear();
    //     //         break;

    //     //     default:
    //     //         $current_start = now()->startOfMonth();
    //     //         $current_end   = now()->endOfMonth();

    //     //         $previous_start = now()->subMonth()->startOfMonth();
    //     //         $previous_end   = now()->subMonth()->endOfMonth();
    //     // }

    //     switch ($period)
    //     {

    //     case 'day':

    //             break;

    //         case 'week':
    //             $current_start = now()->startOfWeek();
    //             $current_end   = now()->endOfWeek();

    //             $previous_start = now()->subWeek()->startOfWeek();
    //             $previous_end   = now()->subWeek()->endOfWeek();
    //             break;

    //         case 'month':
    //             $current_start = now()->startOfMonth();
    //             $current_end   = now()->endOfMonth();

    //             $previous_start = now()->subMonth()->startOfMonth();
    //             $previous_end   = now()->subMonth()->endOfMonth();
    //             break;


    //         default:
    //             $current_start = now()->startOfMonth();
    //             $current_end   = now()->endOfMonth();

    //             $previous_start = now()->subMonth()->startOfMonth();
    //             $previous_end   = now()->subMonth()->endOfMonth();
    //     }

    //     return
    //     [
    //         $current_start,
    //         $current_end,
    //         $previous_start,
    //         $previous_end
    //     ];
    // }
    // //=====================================================================
    // public function investorPerformanceSummary($investor_id, $period)
    // {
    //     [$current_start, $current_end, $previous_start, $previous_end] = $this->getDateRanges($period);

    //     // ============================
    //     // الحجوزات (الفترة الحالية)
    //     $current_bookings = BoothBooking::where('investor_id', $investor_id)
    //         ->whereNotNull('approved_at')
    //         ->whereBetween('approved_at', [$current_start, $current_end])
    //         ->get();

    //     $current_total_bookings = $current_bookings->whereIn('status', ['approved', 'finished'])->count();
    //     $current_active_booths  = $current_bookings->where('status', 'approved')->count();

    //     $previous_bookings = BoothBooking::where('investor_id', $investor_id)
    //         ->whereNotNull('approved_at')
    //         ->whereBetween('approved_at', [$previous_start, $previous_end])
    //         ->get();

    //     $previous_total_bookings = $previous_bookings->whereIn('status', ['approved', 'finished'])->count();
    //     $previous_active_booths  = $previous_bookings->where('status', 'approved')->count();

    //     // ============================
    //     // الفعاليات (الفترة الحالية)
    //     $all_investor_bookings = BoothBooking::where('investor_id', $investor_id)->pluck('id');

    //     $current_events = Event::whereIn('booth_booking_id', $all_investor_bookings)
    //         ->whereBetween('created_at', [$current_start, $current_end])
    //         ->get();

    //     $current_published_events = $current_events->count();
    //     $current_engagement = $current_events->sum('scanned_count') + $current_events->sum('registered_count');


    //     $previous_events = Event::whereIn('booth_booking_id', $all_investor_bookings)
    //         ->whereBetween('created_at', [$previous_start, $previous_end])
    //         ->get();

    //     $previous_published_events = $previous_events->count();
    //     $previous_engagement = $previous_events->sum('scanned_count') + $previous_events->sum('registered_count');
    //     // ============================

    //     // حساب نسب النمو
    //     $growth_total_bookings   = $this->growthRate($current_total_bookings, $previous_total_bookings);
    //     $growth_active_booths    = $this->growthRate($current_active_booths, $previous_active_booths);
    //     $growth_published_events = $this->growthRate($current_published_events, $previous_published_events);
    //     $growth_engagement       = $this->growthRate($current_engagement, $previous_engagement);


    //     return response()->json([
    //         'summary' =>
    //         [
    //             'total_bookings' =>
    //             [
    //                 'value' => $current_total_bookings,
    //                 'growth' => $growth_total_bookings
    //             ],
    //             'active_booths' =>
    //             [
    //                 'value' => $current_active_booths,
    //                 'growth' => $growth_active_booths
    //             ],
    //             'published_events' =>
    //             [
    //                 'value' => $current_published_events,
    //                 'growth' => $growth_published_events
    //             ],
    //             'total_engagement' =>
    //             [
    //                 'value' => $current_engagement,
    //                 'growth' => $growth_engagement
    //             ],
    //         ],

    //         'period' => $period,
    //         'current_range' =>
    //         [
    //             'start' => $current_start->format('Y-m-d'),
    //             'end' => $current_end->format('Y-m-d'),
    //         ],
    //         'previous_range' =>
    //         [
    //             'start' => $previous_start->format('Y-m-d'),
    //             'end' => $previous_end->format('Y-m-d'),
    //         ]
    //     ], 200);
    // }
    //=====================================================================
    // public function dashboard(Request $request)
    // {
    //     $period = $request->query('period', 'this_month'); // قيمة افتراضية
    //     $investor_id = Auth::user()->investor->id;

    //     return $this->investorPerformanceSummary($investor_id, $period);
    // }





    //=====================================================================
    //o
    //=====================================================================
    // public function getExhibitionInvestorsStatistics()
    // {
    //     $organizer = Auth::user()->organizer;
    //     $exhibition_id = $organizer->exhibition->id;

    //     //الشركات المشاركة في المعرض
    //     $investors = Investor::whereHas('boothBookings', function ($q) use ($exhibition_id)
    //     {
    //         $q->whereHas('booth', function ($q2) use ($exhibition_id)
    //         {
    //             $q2->where('exhibition_id', $exhibition_id);
    //         });
    //     })->get();

    //     $investor_ids = $investors->pluck('id');

    //     //جميع حجوزات الأجنحة الخاصة بالمعرض
    //     $booth_bookings = BoothBooking::whereIn('investor_id', $investor_ids)
    //         ->whereHas('booth', function ($q) use ($exhibition_id)
    //         {
    //             $q->where('exhibition_id', $exhibition_id);
    //         })
    //         ->get();


    //     $companies_count = $investors->count();
    //     $booked_booths_count = $booth_bookings->count();//$organizer->exhibition->total_booths - $organizer->exhibition->available_booths
    //     $total_value = $booth_bookings->sum('total_price');
    //     $paid_amount = $booth_bookings->sum('paid_amount');

    //     $companies_data = $investors->map(function ($inv) use ($booth_bookings)
    //     {

    //         $company_bookings = $booth_bookings->where('investor_id', $inv->id);

    //         $company_total_value = $company_bookings->sum('total_price');
    //         $company_paid_amount = $company_bookings->sum('paid_amount');
    //         $rest = $company_total_value - $company_paid_amount;

    //         $payment_rate = $company_total_value > 0
    //             ? round(($company_paid_amount / $company_total_value) * 100, 2)
    //             : 0;

    //         $booths = $company_bookings->map(function ($booking)
    //         {
    //             return
    //             [
    //                 'booth_number' => $booking->booth->number,
    //                 'area' => $booking->booth->area,
    //             ];
    //         });

    //         return
    //         [
    //             'company_name' => $inv->company_name,
    //             'email' => $inv->user->email,
    //             'phone' => $inv->user->phone,

    //             'total_value' => $company_total_value,
    //             'paid_amount' => $company_paid_amount,
    //             'payment_rate' => $payment_rate,
    //             'rest' => $rest,

    //             'booths' => $booths,
    //         ];
    //     });

    //     return response()->json([
    //         'companies_count' => $companies_count,
    //         'booked_booths_count' => $booked_booths_count,
    //         'total_value' => $total_value,
    //         'paid_amount' => $paid_amount,
    //         'companies' => $companies_data
    //     ], 200);
    // }
    //=====================================================================

}

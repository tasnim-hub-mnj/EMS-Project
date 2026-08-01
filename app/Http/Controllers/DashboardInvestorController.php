<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothBooking;
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

        $query = Exhibition::where('copy_status', 'active')
            ->where('location', $investor->location)
            ->where('type', $investor->activity_type)
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->where('available_booths', '>', 0)
            ->orderBy('start_date', 'asc');

        $exhibitions = $query->paginate($per_page, ['*'], 'page', $page);

        $data = $exhibitions->map(function ($ex)
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
                'available_booths' => $ex->available_booths,
                'sectors'          => $ex->sectors ?? [],
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $exhibitions->currentPage(),
                'last_page'    => $exhibitions->lastPage(),
                'per_page'     => $exhibitions->perPage(),
                'total'        => $exhibitions->total(),
            ]
        ], 200);
    }
    //=====================================================================
    public function featuredSponsorEvents(Request $request)//✅
    {
        $investor = Auth::user()->investor;

        $page     = $request->query('page', 1);
        $per_page = $request->query('per_page', 5);

        $query = SponsorEvent::where('copy_status', 'active')
            ->where('type', $investor->activity_type)
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->orderBy('start_time', 'asc');

        $events = $query->paginate($per_page, ['*'], 'page', $page);

        $data = $events->map(function ($ev)
        {
            $ex = $ev->exhibition;

            return
            [
                'id'                    => $ev->id,
                'name'                  => $ev->name,
                'type'                  => $ev->type,
                'exhibition_id'         => $ev->exhibition_id,
                'exhibition_name'       => $ex->name ?? null,
                'exhibition_image_url'  => $ex->exhibitionImages->pluck('image')->first() ?? null,
                'date'                  => Carbon::parse($ev->start_time)->format('Y-m-d'),
                'start_time'            => Carbon::parse($ev->start_time)->format('H:i'),
                'end_time'              => Carbon::parse($ev->end_time)->format('H:i'),
                'place'                 => $ev->place,
                'listing_days'          => $ev->duration_days,
                'description'           => $ev->description,
                'duration_options'      => $this->buildDurationOptions($ev),
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
        $options = [];

        $daily = $event->daily_price;
        $totalDays = $event->duration_days;

        // Always add: One day
        $options[] =
        [
            'label' => 'One day',
            'days'  => 1,
            'price' => $daily,
        ];

        // Add: Two days ONLY if event duration > 2
        if ($totalDays > 2)
        {
            $options[] =
            [
                'label' => 'Two days',
                'days'  => 2,
                'price' => $daily * 2,
            ];
        }

        // Always add: Full event
        $options[] =
        [
            'label' => "Full event ($totalDays days)",
            'days'  => $totalDays,
            'price' => $daily * $totalDays,
        ];

        return $options;
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
            'total_engagement' => $current_engagement
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
        $exhibitions = Exhibition::whereIn('status', ['upcoming', 'ongoing'])
            ->where('copy_status', 'active')
            ->orderBy('start_date', 'asc')
            ->get();

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
    public function getExhibitionInvestorsStatistics()
    {
        $organizer = Auth::user()->organizer;
        $exhibition_id = $organizer->exhibition->id;

        //الشركات المشاركة في المعرض
        $investors = Investor::whereHas('boothBookings', function ($q) use ($exhibition_id)
        {
            $q->whereHas('booth', function ($q2) use ($exhibition_id)
            {
                $q2->where('exhibition_id', $exhibition_id);
            });
        })->get();

        $investor_ids = $investors->pluck('id');

        //جميع حجوزات الأجنحة الخاصة بالمعرض
        $booth_bookings = BoothBooking::whereIn('investor_id', $investor_ids)
            ->whereHas('booth', function ($q) use ($exhibition_id)
            {
                $q->where('exhibition_id', $exhibition_id);
            })
            ->get();


        $companies_count = $investors->count();
        $booked_booths_count = $booth_bookings->count();//$organizer->exhibition->total_booths - $organizer->exhibition->available_booths
        $total_value = $booth_bookings->sum('total_price');
        $paid_amount = $booth_bookings->sum('paid_amount');

        $companies_data = $investors->map(function ($inv) use ($booth_bookings)
        {

            $company_bookings = $booth_bookings->where('investor_id', $inv->id);

            $company_total_value = $company_bookings->sum('total_price');
            $company_paid_amount = $company_bookings->sum('paid_amount');
            $rest = $company_total_value - $company_paid_amount;

            $payment_rate = $company_total_value > 0
                ? round(($company_paid_amount / $company_total_value) * 100, 2)
                : 0;

            $booths = $company_bookings->map(function ($booking)
            {
                return
                [
                    'booth_number' => $booking->booth->number,
                    'area' => $booking->booth->area,
                ];
            });

            return
            [
                'company_name' => $inv->company_name,
                'email' => $inv->user->email,
                'phone' => $inv->user->phone,

                'total_value' => $company_total_value,
                'paid_amount' => $company_paid_amount,
                'payment_rate' => $payment_rate,
                'rest' => $rest,

                'booths' => $booths,
            ];
        });

        return response()->json([
            'companies_count' => $companies_count,
            'booked_booths_count' => $booked_booths_count,
            'total_value' => $total_value,
            'paid_amount' => $paid_amount,
            'companies' => $companies_data
        ], 200);
    }
    //=====================================================================

}

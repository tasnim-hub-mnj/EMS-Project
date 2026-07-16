<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\Investor;
use App\Models\Report;
use App\Models\SponsorshipBooking;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardInvestorController extends Controller
{
    private function getDateRange($filter)//فلترة المدة
    {
        switch ($filter) {
            case '3months':
                return [
                    'start' => now()->subMonths(3)->startOfDay(),
                    'end'   => now()->endOfDay(),
                ];

            case 'year':
                return [
                    'start' => now()->startOfYear(),
                    'end'   => now()->endOfDay(),
                ];

            default: // this_month
                return [
                    'start' => now()->startOfMonth(),
                    'end'   => now()->endOfDay(),
                ];
        }
    }
    //=====================================================================
    //الأجنحة النشطة
    public function getActiveBoothsStats($investorId, $filter)
    {
        $range = $this->getDateRange($filter);

        // الأجنحة النشطة ضمن الفترة الحالية
        $currentActive = BoothBooking::where('investor_id', $investorId)
            ->where('status', 'approved')
            ->get()
            ->filter(function ($booking) use ($range) {
                $start = Carbon::parse($booking->booked_at);
                $end   = $start->copy()->addDays($booking->duration_days);

                return Carbon::now()->between($start, $end);
            })
            ->count();

        // الفترة السابقة (نفس المدة لكن قبلها)
        $previousRange = [
            'start' => $range['start']->copy()->subMonths(1),
            'end'   => $range['end']->copy()->subMonths(1),
        ];

        $previousActive = BoothBooking::where('investor_id', $investorId)
            ->where('status', 'approved')
            ->get()
            ->filter(function ($booking) use ($previousRange)
            {
                $start = Carbon::parse($booking->booked_at);
                $end   = $start->copy()->addDays($booking->duration_days);

                return Carbon::now()->subMonth()->between($start, $end);
            })->count();

        $growth = $previousActive > 0
            ? (($currentActive - $previousActive) / $previousActive) * 100
            : 100;

        return [
            'value'  => $currentActive,
            'growth' => round($growth, 1),
        ];
    }
    //=====================================================================
    // إجمالي حجوزات البوثات
    public function getTotalBookingsStats($investorId, $filter)
    {
        $range = $this->getDateRange($filter);

        $current = BoothBooking::where('investor_id', $investorId)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->count();

        $previousRange = [
            'start' => $range['start']->copy()->subMonths(1),
            'end'   => $range['end']->copy()->subMonths(1),
        ];

        $previous = BoothBooking::where('investor_id', $investorId)
            ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
            ->count();

        $growth = $previous > 0
            ? (($current - $previous) / $previous) * 100
            : 100;

        return [
            'value'  => $current,
            'growth' => round($growth, 1),
        ];
    }
    //=====================================================================
    //إجمالي التفاعل
    public function getTotalInteractionStats($investorId, $filter)
    {
        $range = $this->getDateRange($filter);

        $current =
            Event::where('investor_id', $investorId)
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->count()
            +
            Ticket::whereHas('event', fn($q) => $q->where('investor_id', $investorId))
                ->whereBetween('requested_at', [$range['start'], $range['end']])
                ->count()
            +
            Favorite::where('investor_id', $investorId)
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->count()
            +
            SponsorshipBooking::where('investor_id', $investorId)
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->count()
            +
            Campaign::where('investor_id', $investorId)
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->count()
            +
            Report::where('investor_id', $investorId)
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->count();

        $previousRange = [
            'start' => $range['start']->copy()->subMonths(1),
            'end'   => $range['end']->copy()->subMonths(1),
        ];

        $previous =
            Event::where('investor_id', $investorId)
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->count()
            +
            Ticket::whereHas('event', fn($q) => $q->where('investor_id', $investorId))
                ->whereBetween('requested_at', [$previousRange['start'], $previousRange['end']])
                ->count()
            +
            Favorite::where('investor_id', $investorId)
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->count()
            +
            SponsorshipBooking::where('investor_id', $investorId)
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->count()
            +
            Campaign::where('investor_id', $investorId)
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->count()
            +
            Report::where('investor_id', $investorId)
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->count();

        $growth = $previous > 0
            ? (($current - $previous) / $previous) * 100
            : 100;

        return [
            'value'  => $current,
            'growth' => round($growth, 1),
        ];
    }
    //=====================================================================
    // الفعاليات المنشورة
    public function getTotalEventsStats($investorId, $filter)
    {
        $range = $this->getDateRange($filter);

        $current = Event::where('investor_id', $investorId)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->count();

        $previousRange = [
            'start' => $range['start']->copy()->subMonths(1),
            'end'   => $range['end']->copy()->subMonths(1),
        ];

        $previous = Event::where('investor_id', $investorId)
            ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
            ->count();

        $growth = $previous > 0
            ? (($current - $previous) / $previous) * 100
            : 100;

        return [
            'value'  => $current,
            'growth' => round($growth, 1),
        ];
    }
    //=====================================================================
    public function getDashboardStats(Request $request)//التابع الرئيسي
    {
        $filter = $request->filter ?? 'month';
        $investorId = Auth::id();

        return response()->json([
            'active_booths'     => $this->getActiveBoothsStats($investorId, $filter),
            'total_bookings'    => $this->getTotalBookingsStats($investorId, $filter),
            'total_interaction' => $this->getTotalInteractionStats($investorId, $filter),
            'total_events'      => $this->getTotalEventsStats($investorId, $filter),
        ], 200);
    }
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

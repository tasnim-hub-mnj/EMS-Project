<?php

namespace App\Http\Controllers;

use App\Models\BoothBooking;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\Favorite;
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
    //__________________________________________________
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

}

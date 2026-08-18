<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvestorSummaryResource;
use App\Models\BoothBooking;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestorsSummaryController extends Controller
{
    public function summary()
    {
        $organizer = Auth::user()->organizer;
        $exhibition_id = $organizer->exhibition->id;

        // الشركات المشاركة في المعرض
        $investors = Investor::whereHas('boothBookings', function ($q) use ($exhibition_id)
        {
            $q->whereHas('booth', function ($q2) use ($exhibition_id)
            {
                $q2->where('exhibition_id', $exhibition_id);
            });
        })->get();

        $investor_ids = $investors->pluck('id');

        // حجوزات الأجنحة
        $booth_bookings = BoothBooking::whereIn('investor_id', $investor_ids)
            ->whereHas('booth', function ($q) use ($exhibition_id)
            {
                $q->where('exhibition_id', $exhibition_id);
            })
            ->get();

        // تجهيز البيانات
        $summary = $investors->map(function ($inv) use ($booth_bookings)
        {
            $company_bookings = $booth_bookings->where('investor_id', $inv->id);

            $inv->total_value = $company_bookings->sum('total_price');
            $inv->paid_amount = $company_bookings->sum('paid_amount');

            // تجهيز الأجنحة
            $inv->booths = $company_bookings->map(function ($booking)
            {
                return
                [
                    'number' => $booking->booth->number,
                    'area' => $booking->booth->area,
                    'status' => $booking->status ?? 'approved',
                ];
            });

            return new InvestorSummaryResource($inv);
        });

        return response()->json($summary, 200);
    }
    //=====================================================================
    public function summaryDetail($investor_id)
    {
        $organizer = Auth::user()->organizer;
        $exhibition_id = $organizer->exhibition->id;

        $investor = Investor::where('id', $investor_id)
            ->whereHas('boothBookings', function ($q) use ($exhibition_id)
            {
                $q->whereHas('booth', function ($q2) use ($exhibition_id)
                {
                    $q2->where('exhibition_id', $exhibition_id);
                });
            })
            ->firstOrFail();

        $bookings = BoothBooking::where('investor_id', $investor_id)
            ->whereHas('booth', function ($q) use ($exhibition_id)
            {
                $q->where('exhibition_id', $exhibition_id);
            })
            ->get();

        $investor->total_value = $bookings->sum('total_price');
        $investor->paid_amount = $bookings->sum('paid_amount');

        $investor->booths = $bookings->map(function ($booking)
        {
            return
            [
                'number' => $booking->booth->number,
                'area' => $booking->booth->area,
                'status' => $booking->status ?? 'approved',
            ];
        });

        return new InvestorSummaryResource($investor);
    }
    //=====================================================================

}

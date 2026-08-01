<?php

namespace App;

use App\Models\BoothBooking;

trait BookingConflictTrait
{
    //Check if there is an APPROVED conflicting booking
    public function hasApprovedConflict($boothId, $start, $end)
    {
        return BoothBooking::where('booth_id', $boothId)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end)
            {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end)
                  {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            })
            ->exists();
    }

    //Get all PENDING conflicting bookings
    public function getPendingConflicts($boothId, $start, $end)
    {
        return BoothBooking::where('booth_id', $boothId)
            ->where('status', 'pending')
            ->where(function ($q) use ($start, $end)
            {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end)
                  {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            })
            ->get();
    }
}

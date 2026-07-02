<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorEventReports extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'investor_id',
        'booth_booking_id',
        'event_id',
        'date',
        'total_registrations',
        'actual_attendance',
        'rating',
        'growth_rate',
    ];

    protected $table = 'investor_event_reports';

    //===============Relationships==================
    public function boothBooking()
    {
        return $this->belongsTo(BoothBooking::class);
    }
    //=================================================
}

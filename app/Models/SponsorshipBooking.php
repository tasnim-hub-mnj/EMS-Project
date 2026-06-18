<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorshipBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'sponsor_event_id',
        'company_name',
        'company_website',
        'company_phone',
        'product_names',
        'selected_duration_label',
        'selected_days',
        'price',
        'status',
        'booked_at',
        'total_visitors',
        'total_attendees',
        'daily_visitors',
        'current_day',
        'total_days',
    ];

    protected $casts = [
        'booked_at'      => 'date',
        'daily_visitors' => 'array',
    ];

    // =================Relationships===================

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=====================================================
    public function sponsorEvent()
    {
        return $this->belongsTo(SponsorEvent::class);
    }
    //=====================================================
    //=====================================================
    //=====================================================
}

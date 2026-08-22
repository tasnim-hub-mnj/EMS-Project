<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorshipBooking extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'investor_id',
        'sponsor_event_id',
        'selected_duration_label',
        'company_name',
        'company_phone',
        'company_website',
        'selected_days',
        'days',
        'total_price',
        'total_days',
        'status',
        'logo',
        'product_names',
        'booked_at',
        'total_visitors',
        'total_attendees',
        'daily_visitors',
        'current_day',
    ];

    protected $table = 'sponsorship_bookings';

    protected $casts =
    [
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
    public function sponsorshipBookingImages()
    {
        return $this->hasMany(SponsorshipBookingImage::class);
    }
    //=====================================================
    public function sponsorshipBookingProductImages()
    {
        return $this->hasMany(SponsorshipBookingProductImage::class, 'sp_b_id', 'id');
    }
    //=====================================================
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SponsorEvent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'sponsor_events';

    protected $casts =
    [
        'duration_options' => 'array',
    ];

    //================================================
    public function getEndDateAttribute()
    {
        if (!$this->date || !$this->listing_days)
        {
            return null;
        }

        return Carbon::parse($this->date)
            ->addDays($this->listing_days - 1)
            ->toDateString();
    }

    //=================Relationships===================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
    public function sponsorshipBookings()
    {
        return $this->hasMany(SponsorshipBooking::class);
    }
    //=====================================================
    public function eventSponsorshipRequests()
    {
        return $this->hasMany(EventSponsorshipRequest::class);
    }
    //=====================================================
    public function sponsorEventImages()
    {
        return $this->hasMany(SponsorEventImage::class);
    }
    //=====================================================
    public function programs()
    {
        return $this->hasMany(SponsorEventProgram::class);
    }
    //=====================================================
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
    //=====================================================
    // public function invitations()
    // {
    //     return $this->hasMany(SponsorEventInvitation::class);
    // }
    //=====================================================
    public function tickets()
    {
        return $this->hasMany(SponserEventTicket::class);
    }
    //=====================================================



}

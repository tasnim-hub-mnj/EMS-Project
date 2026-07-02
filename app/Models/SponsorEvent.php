<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SponsorEvent extends Model
{
    use HasFactory;

    protected $fillable =
        [
            'exhibition_id',
            'name',
            'type',
            'by',
            'place',
            'start_time',
            'end_time',
            'description',
            'is_general_invitation',
            'ticket_price',
            'max_participants',
            'listing_days',
            'duration_options',
            'registered_count',
            'total_seats',
            'scanned_count',
            'status',
            'copy_status'
        ];

    protected $table = 'sponsor_events';

    protected $casts =
        [
            'duration_options' => 'array',
        ];

    //===============Relationships==================

    public function getEndDateAttribute()
    {
        if (!$this->date || !$this->listing_days) {
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
    public function sponsorEventImages()
    {
        return $this->hasMany(SponsorEventImage::class);
    }
    //=====================================================
    public function sponsorEventPrograms()
    {
        return $this->hasMany(SponsorEventProgram::class);
    }
    //=====================================================
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
    //=====================================================
    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }
    //=====================================================
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Ticket;

class Event extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'investor_id',
        'booth_booking_id',
        'name',
        'type',
        'by',
        'date',
        'start_time',
        'end_time',
        'duration_days',
        'description',
        'video_promo_url',
        'is_general_invitation',
        'has_bookable_seats',
        'max_participants',
        'requires_booking',
        'ticket_price',
        'registered_count',
        'total_seats',
        'scanned_count',
        'status',
        'current_day',
    ];

    protected $table = 'events';

    // =================Relationships===================
    public function boothBooking()
    {
        return $this->belongsTo(BoothBooking::class);
    }
    //=====================================================
    public function eventTickets()
    {
        return $this->hasMany(EventTicket::class, 'event_id');
    }
    //=====================================================
    public function favorites()//v
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
    //=====================================================
    public function eventImages()
    {
        return $this->hasMany(EventImage::class);
    }
    //=====================================================
    // public function schedule()
    // {
    //     return $this->hasMany(VisitorSchedule::class);
    // }
    //=====================================================
}

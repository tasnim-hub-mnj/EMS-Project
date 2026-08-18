<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Ticket;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'events';

    // =================Relationships===================
    public function boothBooking()
    {
        return $this->belongsTo(BoothBooking::class, 'booth_booking_id');
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
    public function exhibition()
    {
        return $this->hasOneThrough(
            Exhibition::class,
            Booth::class,
            'id',
            'id',
            'booth_id',
            'exhibition_id'
        );
    }
}
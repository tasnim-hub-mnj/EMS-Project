<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponserEventTicket extends Model
{
    protected $guarded = [];

    protected $casts =
        [
            'booked_at' => 'datetime',
            'amount' => 'float',
        ];
    //==========================================

    //============================================
    public function sponsorEvent()
    {
        return $this->belongsTo(SponsorEvent::class, 'sponsor_event_id');
    }
    //============================================
    public function visitor()
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }
    //============================================
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSponsorshipRequest extends Model
{
    protected $guarded = [];

    protected $table = 'event_sponsorship_requests';

    //===============Relationships==================
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
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
}

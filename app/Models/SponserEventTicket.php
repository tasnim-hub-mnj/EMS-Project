<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponserEventTicket extends Model
{
    protected $fillable = [
        'sponsor_event_id',
        'user_id',
        'name',
        'email',
        'phone',
        'status',
        'qr_code',
        'amount',
        'booked_at',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // الفعالية الراعية
    public function sponsorEvent()
    {
        return $this->belongsTo(SponsorEvent::class, 'sponsor_event_id');
    }
    public function visitor()
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }


}

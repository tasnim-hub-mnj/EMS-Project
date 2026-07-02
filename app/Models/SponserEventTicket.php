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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // الفعالية الراعية
    public function sponsorEvent()
    {
        return $this->belongsTo(SponsorEvent::class, 'sponsor_event_id');
    }


}

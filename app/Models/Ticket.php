<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable =
    [
        'user_id',
        'exhibition_id',
        'qr_code',
        'status',
        'amount',
        'booked_at'
    ];

    protected $table = 'tickets';

    protected $casts =
    [
        'requested_at' => 'datetime',
    ];

    //===============Relationships==================


    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

}


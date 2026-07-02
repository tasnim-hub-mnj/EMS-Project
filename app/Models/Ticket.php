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
    public function visitor()
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }
    //=====================================================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================

}


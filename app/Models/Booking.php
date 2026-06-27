<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable =
        [
            'user_id',
            'exhibition_id',
            'event_id',
            'type',
            'qr_code',
            'status',
            'amount',
            'booked_at'
        ];

    protected $casts = ['booked_at' => 'datetime'];

    //======================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    //======================================

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //======================================

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    //======================================
}

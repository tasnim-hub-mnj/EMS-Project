<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'exhibition_id',
        'qr_code',
        'status',
        'amount',
        'booked_at',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // الزائر الذي حجز التذكرة
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // المعرض الذي تم حجز تذكرته
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTicket extends Model
{
    protected $fillable = [
        'investor_id',
        'sponsorEvent_id',
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

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    // علاقة مع اليوزر (الزائر الحقيقي)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة مع المستثمر صاحب الجناح
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    // علاقة مع الفعالية داخل الجناح
    public function sponsorEvent()
    {
        return $this->belongsTo(SponsorEvent::class, 'sponsorEvent_id');
    }
}

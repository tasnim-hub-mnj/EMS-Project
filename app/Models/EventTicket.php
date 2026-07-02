<?php

namespace App\Models;

<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
>>>>>>> 8df673a1e1d5d51d983c7999a80fbc47933e1272
use Illuminate\Database\Eloquent\Model;

class EventTicket extends Model
{
<<<<<<< HEAD
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
=======
    use HasFactory;

    protected $fillable =
    [

    ];

    protected $table = 'event_tickets';
    //===============Relationships==================
    public function visitor()
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }
    //=====================================================
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    //=====================================================

>>>>>>> 8df673a1e1d5d51d983c7999a80fbc47933e1272
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Ticket;

class Event extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'investor_id',
        'booth_booking_id',
        'name',
        'type',
        'by',
        'date',
        'start_time',
        'end_time',
        'duration_days',
        'description',
        'video_promo_url',
        'is_general_invitation',
        'has_bookable_seats',
        'max_participants',
        'requires_booking',
        'ticket_price',
        'registered_count',
        'total_seats',
        'scanned_count',
        'status',
        'current_day',
    ];

    protected $table = 'events';

    // =================Relationships===================
    // كل فعالية مرتبطة بمستثمر صاحب الجناح
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=====================================================
    // كل فعالية تعود إلى جناح محدد
    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }
    //=====================================================
    // كل فعالية يمكن أن يكون لها طلبات تذاكر متعددة
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'event_id');
    }
    //=====================================================
    // الفعالية يمكن أن تظهر في المفضلة
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
    //=====================================================
     public function schedule()
    {
        return $this->hasMany(VisitorSchedule::class);
    }
}

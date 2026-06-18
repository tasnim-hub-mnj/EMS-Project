<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Ticket;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'booth_id',
        'name',
        'type',
        'date',
        'time',
        'duration_days',
        'max_participants',
        'registered_count',
        'status',
        'description',
        'requires_booking',
        'place',
        'has_bookable_seats',
        'total_seats',
        'booked_seats',
        'sold_tickets',
        'ticket_price',
        'is_general_invitation',
        'video_promo_url',
        'company_images',
        'current_day',
        'total_event_days',
        'daily_attendees',
        'scanned_count',
    ];

    protected $casts = [
        'date'              => 'date',
        'time'              => 'datetime:H:i',
        'requires_booking'  => 'boolean',
        'has_bookable_seats'=> 'boolean',
        'is_general_invitation' => 'boolean',
        'company_images'    => 'array',
        'daily_attendees'   => 'array',
    ];

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

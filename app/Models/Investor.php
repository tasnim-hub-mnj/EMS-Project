<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'company_name',
        'trade_name',
        'location',
        'website',
        'activity_type',
        'terms_accepted',
        'bio',
        'logo',
    ];

    protected $table = 'investors';

    protected $casts =
    [
        'terms_accepted' => 'boolean',
    ];

    // =================Relationships===================
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //=====================================================
    // المستثمر يمكنه حجز عدة أجنحة
    public function boothBookings()
    {
        return $this->hasMany(BoothBooking::class);
    }
    //=====================================================
    // المستثمر لديه طلبات حجز رعاية متعددة
    public function sponsorshipBookings()
    {
        return $this->hasMany(SponsorshipBooking::class);
    }
    //=====================================================
    // الفعاليات التي أنشأها المستثمر
    public function events()
    {
        return $this->hasMany(Event::class);
    }
    //=====================================================
}

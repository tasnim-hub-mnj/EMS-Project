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

    //=================Relationships===================
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //=====================================================
    public function boothBookings()
    {
        return $this->hasMany(BoothBooking::class,'investor_id');
    }
    //=====================================================
    public function events()
    {
        return $this->hasMany(Event::class);
    }
    //=====================================================
    public function sponsorshipBookings()
    {
        return $this->hasMany(SponsorshipBooking::class);
    }
    //=====================================================
    public function sponsorshipRequests()
    {
        return $this->hasMany(SponsorshipRequest::class);
    }
    //=====================================================
    public function socialLinks()
    {
        return $this->hasMany(SocialLink::class);
    }
    //=====================================================
    public function investorPerformanceReports()
    {
        return $this->hasOne(InvestorPerformanceReports::class);
    }
    //=================================================


}

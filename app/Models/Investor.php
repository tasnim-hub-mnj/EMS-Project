<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use HasFactory;

    protected $guarded = [];

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
    public function booths()
    {
        return $this->hasManyThrough(
            Booth::class,            // الجدول النهائي
            BoothBooking::class,     // الجدول الوسيط
            'investor_id',           // BoothBooking.investor_id
            'id',                    // Booth.id
            'id',                    // Investor.id
            'booth_id'               // BoothBooking.booth_id
        );
    }
    //=====================================================
    public function boothBookings()
    {
        return $this->hasMany(BoothBooking::class, 'investor_id');
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
    public function eventSponsorshipRequests()
    {
        return $this->hasMany(EventSponsorshipRequest::class);
    }
    //=====================================================
    public function socialLinks()
    {
        return $this->hasMany(SocialLink::class);
    }
    //=================================================
    public function investorVisitorReports()//******************1
    {
        return $this->hasMany(InvestorVisitorReports::class);
    }
    //=================================================
    public function investorBoothReports()//******************2
    {
        return $this->hasMany(InvestorBoothReports::class);
    }
    //=================================================
    public function investorEventReports()//******************3
    {
        return $this->hasMany(InvestorEventReports::class);
    }
    //=====================================================
    public function InvestorSponsorshipsReports()//******************4
    {
        return $this->hasOne(InvestorSponsorshipsReports::class);
    }
    //=================================================



}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exhibition extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'exhibitions';

    protected $casts =
        [
            'map' => 'array',
            'sectors' => 'array',
            'extra_services' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
        ];

    //===============Relationships==================
    public function organizer()
    {
        return $this->belongsTo(Organizer::class, 'organizer_id');
    }
    //=====================================================
    public function booths()
    {
        return $this->hasMany(Booth::class, 'exhibition_id', 'id');
    }
    //=====================================================
    public function sponsorEvents()
    {
        return $this->hasMany(SponsorEvent::class);
    }
    //=====================================================
    public function eventSponsorshipRequests()
    {
        return $this->hasMany(EventSponsorshipRequest::class);
    }
    //=====================================================
    public function sponsors()
    {
        return $this->hasMany(Sponsor::class);
    }
    //=====================================================
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'exhibition_id');
    }
    //=====================================================
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
    //=====================================================
    public function exhibitionImages()
    {
        return $this->hasMany(ExhibitionImage::class);
    }
    //=====================================================
    // public function map()
    // {
    //     return $this->hasOne(Map::class)->latestOfMany();
    // }
    public function maps()
    {
        return $this->hasMany(Map::class);
    }
    //_____________________________________________________
    public function latestMap()
    {
        return $this->hasOne(Map::class)->latestOfMany();
    }
    //=====================================================
    public function publishedMap()
    {
        return $this->hasOne(Map::class)->where('status', 'published');
    }
    //=====================================================
    public function exhibitionReviews()
    {
        return $this->hasMany(ExhibitionReview::class);
    }
    //=====================================================
    public function staffs()
    {
        return $this->belongsToMany(StaffMember::class,'staff_roles');
    }
    //=====================================================
    public function copies()
    {
        return $this->hasMany(Copy::class);
    }
    //=====================================================
    public function portalLink()
    {
        return $this->hasMany(PortalLink::class, 'exhibition_id');
    }
    //=====================================================
    //=====================================================
}





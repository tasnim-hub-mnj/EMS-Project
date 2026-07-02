<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exhibition extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'organizer_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'location',
        'description',
        'city',
        'status',
        'copy_status',
        'available_booths',
        'total_booths',
        'total_sponser_events',
        'visitors_count',
        'sectors',
        'extra_services',
        'working_hours',
        'is_paid',
    ];

    protected $table = 'exhibitions';

    protected $casts =
    [
        'sectors'        => 'array',
        'extra_services' => 'array',
        'start_date'     => 'date',
        'end_date'       => 'date',
    ];

    //===============Relationships==================
    public function organizer()
    {
        return $this->belongsTo(Organizer::class, 'organizer_id');
    }
    //=====================================================
    public function booths()
    {
        return $this->hasMany(Booth::class);
    }
    //=====================================================
    public function sponsorEvents()
    {
        return $this->hasMany(SponsorEvent::class);
    }
    //=====================================================
    public function sponsors()
    {
        return $this->hasMany(Sponsor::class);
    }
    //=====================================================
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
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
    public function exhibitionReviews()
    {
        return $this->hasMany(ExhibitionReview::class);
    }
    //=====================================================
    public function staffs()
    {
        return $this->hasMany(StaffMember::class);
    }
    //=====================================================
    public function copies()
    {
        return $this->hasMany(Copy::class);
    }
    //=====================================================
}





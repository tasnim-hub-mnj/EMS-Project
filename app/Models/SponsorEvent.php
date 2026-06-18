<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SponsorEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'exhibition_id',
        'name',
        'type',
        'date',
        'start_time',
        'end_time',
        'place',
        'listing_days',
        'description',
        'duration_options',
    ];

    protected $casts = [
        'duration_options' => 'array',
    ];


    public function getEndDateAttribute()
    {
        if (!$this->date || !$this->listing_days) {
            return null;
        }

        return Carbon::parse($this->date)
                    ->addDays($this->listing_days - 1)
                    ->toDateString();
    }

    //=================Relationships===================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
    public function bookings()
    {
        return $this->hasMany(SponsorshipBooking::class);
    }
    //=====================================================
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
}

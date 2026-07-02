<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorshipBookingImage extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'sponsorship_booking_id',
        'url',
    ];

    protected $table = 'sponsorship_booking_images';

    //===============Relationships==================
    public function sponsorshipBooking()
    {
        return $this->belongsTo(SponsorshipBooking::class);
    }
    //=====================================================
}

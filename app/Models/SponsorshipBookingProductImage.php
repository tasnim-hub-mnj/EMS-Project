<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorshipBookingProductImage extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'sponsorship_booking_id',
        'product_name',
        'image',
    ];

    protected $table = 'sponsorship_booking_product_images';

    //===============Relationships==================
    public function sponsorshipBooking()
    {
        return $this->belongsTo(SponsorshipBooking::class);
    }
    //=====================================================
}

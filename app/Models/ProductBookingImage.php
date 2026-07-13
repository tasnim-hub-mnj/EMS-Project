<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBookingImage extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'booth_booking_id',
        'image_p',
    ];

    protected $table = 'product_booking_image';
    //===============Relationships==================
    public function boothBooking()
    {
        return $this->belongsTo(BoothBooking::class);
    }
    //=================================================
}

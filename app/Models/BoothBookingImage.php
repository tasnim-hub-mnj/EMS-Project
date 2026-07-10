<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoothBookingImage extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'booth_booking_id',
        'url',
        'type'
    ];

    protected $table = 'booth_images';
    //===============Relationships==================
    public function boothBooking()
    {
        return $this->belongsTo(BoothBooking::class);
    }
    //=================================================
}

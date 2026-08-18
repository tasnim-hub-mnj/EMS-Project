<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoothBooking extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'booth_bookings';

    protected $casts =
        [
            'days' => 'integer',
            'total_price' => 'float',
            'booked_at' => 'date',
            'approved_at' => 'date',
            'additional_services' => 'array',
            'services_products' => 'array',
        ];
    //=================Relationships===================
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=================================================
    public function booth()
    {
        return $this->belongsTo(Booth::class, 'booth_id');
    }
    //=================================================
    public function copy()
    {
        return $this->belongsTo(Copy::class, 'copy_id');
    }
    //=================================================
    public function events()
    {
        return $this->hasMany(Event::class, 'booth_booking_id');
    }
    //=================================================
    public function boothBookingImages()
    {
        return $this->hasMany(BoothBookingImage::class);
    }
    //=================================================
    public function productBookingImages()
    {
        return $this->hasMany(ProductBookingImage::class);
    }
    //=================================================
    public function investorVisitorReports()//******************
    {
        return $this->hasMany(InvestorVisitorReports::class);
    }
    //=================================================
    public function investorBoothReports()//******************
    {
        return $this->hasMany(InvestorBoothReports::class);
    }
    //=================================================
    public function investorEventReports()//******************
    {
        return $this->hasMany(InvestorEventReports::class);
    }
    //=================================================
    





}

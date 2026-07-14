<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoothBooking extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'investor_id',
        'booth_id',
        'duration_days',
        'notes',
        'total_price',
        'paid_amount',
        'services_products',
        'status',
        'booked_at',
    ];

    protected $table = 'booth_bookings';

    protected $casts =
    [
        'duration_days'    => 'integer',
        'total_price'      => 'float',
        'booked_at'        => 'date',
    ];
    //=================Relationships===================
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=================================================
    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }
    //=================================================
    public function events()
    {
        return $this->hasMany(Event::class,'booth_booking_id');
    }
    //=================================================
    public function boothBookingImages()
    {
        return $this->hasMany(BoothBookingImage::class);
    }
    //=================================================
    public function productBookingImages()
    {
        return $this->hasMany(BoothBookingImage::class);
    }
    //=================================================
    public function investorBoothReports()
    {
        return $this->hasMany(InvestorBoothReports::class);
    }
    //=================================================
    public function investorEventReports()
    {
        return $this->hasMany(InvestorEventReports::class);
    }
    //=================================================
    public function investorVisitorReports()
    {
        return $this->hasMany(InvestorVisitorReports::class);
    }
    //=================================================





}

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
        'start_date',
        'end_date',
        'days',
        'additional_services',
        'notes',
        'total_price',
        'paid_amount',
        'services_products',
        'status',
        'booked_at',
        'approved_at',
        'cover_image'
    ];

    protected $table = 'booth_bookings';

    protected $casts =
    [
        'days'    => 'integer',
        'total_price'      => 'float',
        'booked_at'        => 'date',
        'approved_at'        => 'date',
        'additional_services' => 'array',
        // 'additional_services' => 'array',
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
        return $this->hasMany(ProductBookingImage::class);
    }
    //=================================================
    // public function investorBoothReports()
    // {
    //     return $this->hasMany(InvestorBoothReports::class);
    // }
    // //=================================================
    // public function investorEventReports()
    // {
    //     return $this->hasMany(InvestorEventReports::class);
    // }
    // //=================================================
    // public function investorVisitorReports()
    // {
    //     return $this->hasMany(InvestorVisitorReports::class);
    // }
    //=================================================
    public function Reports()
    {
        return $this->hasMany(ReportInvestor::class);
    }
    //=================================================





}

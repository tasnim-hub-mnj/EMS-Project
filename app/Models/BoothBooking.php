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
    //=========================================================
    protected static function booted()//حساب مجمل السعر
    {
        static::creating(function ($booking)
        {
            // جلب المعرض من خلال البوث
            $exhibition = $booking->booth->exhibition;
            $booth=$booking->booth;
            // جلب الأسعار من JSON
            $services = $booth->services ?? [];
            $prices=$services->map(function($s) use ($services)
            {

            });

            // سعر البوث
            $boothPrice = $booking->booth->price;

            // حساب السعر الكلي
            $booking->total_price =
                ($boothPrice * $booking->duration_days);
                // +;


            // تعبئة booked_at تلقائياً
            if (empty($booking->booked_at))
            {
                $booking->booked_at = now();
            }
        });
    }

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
    public function boothImages()
    {
        return $this->hasMany(BoothImage::class);
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

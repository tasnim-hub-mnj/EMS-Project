<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoothBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'booth_id',
        'duration_days',
        'notes',
        'screen_service',
        'setup_service',
        'security_service',
        'cleaning_service',
        'total_price',
        'status',
        'booked_at',
    ];

    protected $casts = [
        'duration_days'    => 'integer',
        'screen_service'   => 'boolean',
        'setup_service'    => 'boolean',
        'security_service' => 'boolean',
        'cleaning_service' => 'boolean',
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

            // جلب الأسعار من JSON
            $services = $exhibition->extra_services ?? [];

            $screen   = $services['screen']   ?? 0;
            $setup    = $services['setup']    ?? 0;
            $security = $services['security'] ?? 0;
            $cleaning = $services['cleaning'] ?? 0;

            // سعر البوث
            $boothPrice = $booking->booth->price;

            // حساب السعر الكلي
            $booking->total_price =
                ($boothPrice * $booking->duration_days)
                + ($booking->screen_service   ? $screen   : 0)
                + ($booking->setup_service    ? $setup    : 0)
                + ($booking->security_service ? $security : 0)
                + ($booking->cleaning_service ? $cleaning : 0);

            // تعبئة booked_at تلقائياً
            if (empty($booking->booked_at))
            {
                $booking->booked_at = now();
            }
        });
    }

    //Relationships==========================================

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=======================================================
    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }



}

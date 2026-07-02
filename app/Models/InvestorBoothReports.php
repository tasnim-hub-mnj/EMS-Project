<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorBoothReports extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'investor_id',
        'booth_booking_id',
        'date',
        'total_visitors',
        'potential_clients',
        'conversions',
        'performance_index',
    ];

    protected $table = 'investor_booth_reports';

    //===============Relationships==================
    public function boothBooking()
    {
        return $this->belongsTo(BoothBooking::class);
    }
    //=================================================

}

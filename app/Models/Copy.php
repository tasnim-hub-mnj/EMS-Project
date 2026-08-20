<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Copy extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'exhibition_id',
        'year',
        'start_date',
        'end_date',
        'copy_status',
        'announced',
        'total_booths',
        'booked_booths',
        'available_booths',
        'pending_requests',
        'visitor_count',
        'expected_visitors',
        'turnout_percent',
        'expected_turnout_percent',
        'revenue',
        'expected_revenue',
        'staff_count',
        'sponsorship_percent',
        'final_booked_booths',
    ];

    protected $table = 'copies';

    //===============Relationships==================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
    public function boothbookings()
    {
        return $this->hasMany(BoothBooking::class);
    }
    //=====================================================
    // public function copyReports()
    // {
    //     return $this->hasMany(CopyReport::class);
    // }
    //=====================================================
    //=====================================================
}

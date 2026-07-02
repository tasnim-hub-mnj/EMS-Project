<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyReport extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'exhibition_id',
        'copy_id',
        'total_visitors',
        'revenues',
        'booking_booths',
        'available_booths',
        'sponsors',
        'booth_id',
        'booth_booking_id',
        'sponsor_id',
        'sponsor_event_id',
        'staff_member_id',
        'visitor_id',
    ];

    protected $table = 'copy_reports';

    //===============Relationships==================
    public function copy()
    {
        return $this->belongsTo(Copy::class);
    }
    //=====================================================
}

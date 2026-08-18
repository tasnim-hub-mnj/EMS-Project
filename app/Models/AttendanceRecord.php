<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    protected $table = 'attendance_records';


    protected $casts =
    [
        'date'    => 'date',
        'check_in'   => 'datetime',
        'check_out'    => 'datetime',
    ];
    //===============Relationships==================
    public function staff()
    {
        return $this->belongsTo(StaffMember::class);
    }
    //=====================================================

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;
    protected $fillable =
    [
        'staff_id',
        'type',
        'date',
        'check_in',
        'check_out',
        'hours_worked',
        'method',
    ];
    protected $table = 'attendance_records';

    protected $casts =
    [
        'date'    => 'date',
        'check_in'   => 'time',
        'check_out'    => 'time',
    ];
    //===============Relationships==================
    public function staff()
    {
        return $this->belongsTo(StaffMember::class);
    }
    //=====================================================
}

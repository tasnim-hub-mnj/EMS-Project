<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts =
    [
        'workDays' => 'array',
    ];

    protected $table = 'staff_members';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($staff)
        {
            // توليد رقم الموظف s1 / s2 / s3
            $lastId = StaffMember::max('id') + 1;
            $staff->number = 's' . $lastId;

            if (empty($staff->qr_code)) {
                $staff->qr_code = 'STAFF:' . $staff->id . ':' . $staff->number;
            }
        });

        static::saving(function ($staff)
        {
            if (empty($staff->qr_code) && !empty($staff->id)) {
                $staff->qr_code = 'STAFF:' . $staff->id . ':' . ($staff->number ?? 'staff');
            }
        });
    }

    //=================Relationships===================
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //=====================================================
    public function exhibitions()
    {
        return $this->belongsToMany(Exhibition::class,'staff_roles');
    }
    //=====================================================
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    //=====================================================
    public function salaries()
    {
        return $this->hasMany(StaffSalary::class);
    }
    //=====================================================
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
    //=====================================================
    // public function staffRole()
    // {
    //     return $this->hasOne(StaffRole::class);
    // }
    //=====================================================
    public function portalLinks()
    {
        return $this->hasMany(PortalLink::class, 'staff_id');
    }
    //=====================================================


}

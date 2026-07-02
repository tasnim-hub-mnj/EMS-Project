<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'exhibition_id',
        'first_name',
        'last_name',
        'type',
        'proffesion',
        'role',
        'availability_date',
        'national_num',
        'exp_salary',
        'bio',
        'scientific_experience',
        'educational_qualifications',
        'skills',
        'status',
        'team',
        'schedule',
        'qr_code',
        'att_rate',
    ];

    protected $table = 'staff_members';

    //=================Relationships===================
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //=====================================================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
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

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSalary extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'exhibition_id',
        'staff_id',
        'year',
        'month',
        'type_staff',
        'salary'
    ];

    protected $table = 'staff_salaries';

    //===============Relationships==================
    public function staff()
    {
        return $this->belongsTo(StaffMember::class);
    }
    //=====================================================
}

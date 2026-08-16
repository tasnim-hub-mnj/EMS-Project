<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSalary extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'staff_salaries';

    //===============Relationships==================
    public function staff()
    {
        return $this->belongsTo(StaffMember::class);
    }
    //=====================================================
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts =
    [
        'assigned_names' => 'array',
        'assigned_staff_ids' => 'array',
    ];

    protected $table = 'tasks';

    //===============Relationships==================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
    public function staff()
    {
        return $this->belongsTo(StaffMember::class);
    }
    //=====================================================
}

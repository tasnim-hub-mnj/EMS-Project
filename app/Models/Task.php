<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'exhibition_id',
        'staff_id',
        'title',
        'description',
        'due_date',
        'status'
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

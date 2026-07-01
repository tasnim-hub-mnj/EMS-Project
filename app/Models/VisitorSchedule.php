<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorSchedule extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'event_id',
        'added_at'
    ];

    protected $table = 'visitor_schedules';

    protected $casts =
    [
        'added_at' => 'datetime'
    ];

    //===============Relationships==================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

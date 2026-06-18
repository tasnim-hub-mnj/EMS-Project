<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorSchedule extends Model
{


    protected $table = 'visitor_schedules';

    protected $fillable = [
        'user_id',
        'event_id',
        'added_at'
        ];

    protected $casts = [
        'added_at' => 'datetime'
        ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

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

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

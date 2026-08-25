<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorSchedule extends Model
{
    use HasFactory;

    protected $fillable =
        [
            'visitor_id',
            'event_id',
            'sponsor_event_id',
            'event_source',
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
    //==============================================

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function sponsorEvent()
    {
        return $this->belongsTo(SponsorEvent::class, 'sponsor_event_id');
    }
    //==============================================
}

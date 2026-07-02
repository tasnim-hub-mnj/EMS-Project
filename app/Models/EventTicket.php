<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTicket extends Model
{
    use HasFactory;

    protected $fillable =
    [

    ];

    protected $table = 'event_tickets';
    //===============Relationships==================
    public function visitor()
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }
    //=====================================================
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    //=====================================================

}

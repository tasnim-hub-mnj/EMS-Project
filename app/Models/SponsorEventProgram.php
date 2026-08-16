<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorEventProgram extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'sponsor_event_programs';

    //===============Relationships==================
    public function sponsorEvent()
    {
        return $this->belongsTo(SponsorEvent::class);
    }
    //=====================================================
}

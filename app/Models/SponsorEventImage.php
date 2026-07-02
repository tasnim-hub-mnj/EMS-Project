<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorEventImage extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'sponsor_event_id',
        'url',
    ];

    protected $table = 'sponsor_event_images';

    //===============Relationships==================
    public function sponsorEvent()
    {
        return $this->belongsTo(SponsorEvent::class);
    }
    //=====================================================

}

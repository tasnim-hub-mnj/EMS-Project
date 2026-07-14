<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorEventInvitation extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'sponsorEvent_id',
        'name',
        'email',
        'phone',
        'method_send',
        'status',
    ];

    protected $table = 'sponsor_event_invitations';

    //===============Relationships==================
    public function sponsorEvent()
    {
        return $this->belongsTo(SponsorEvent::class);
    }
    //=====================================================
}

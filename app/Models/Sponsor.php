<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    protected $guarded = [];

    //===============Relationships==================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
    public function sponsorshipRequests()
    {
        return $this->hasMany(SponsorshipRequest::class);
    }
    //=====================================================
}

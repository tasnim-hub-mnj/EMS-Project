<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorshipRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'sponsorship_requests';

    //===============Relationships==================
    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }
    //=====================================================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
}

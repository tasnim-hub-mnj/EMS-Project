<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'investor_id',
        'link',
        'type'
    ];

    protected $table = 'social_links';

    //===============Relationships==================
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=====================================================
}

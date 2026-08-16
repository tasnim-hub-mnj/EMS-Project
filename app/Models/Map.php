<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    protected $guarded = [];

    protected $casts =
    [
        'published_at' => 'datetime',
        'map_json' => 'array',
    ];

    protected $table = 'maps';

    //===============Relationships==================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //===================================================
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    //===================================================

}

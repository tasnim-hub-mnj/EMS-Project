<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoothImage extends Model
{
     protected $fillable = [
        'booth_id',
         'url',
         'type'
         ];

    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }
}

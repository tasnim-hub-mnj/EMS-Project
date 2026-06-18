<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExhibitionImage extends Model
{
     protected $fillable = [
        'exhibition_id',
         'url',
         'order'
         ];

     public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
}

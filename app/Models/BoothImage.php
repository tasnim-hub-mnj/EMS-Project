<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoothImage extends Model
{
    protected $fillable =
    [
        'booth_id',
        'image',
    ];

    protected $table = 'booth_images';

    //===============Relationships==================
    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }
    //=====================================================
}

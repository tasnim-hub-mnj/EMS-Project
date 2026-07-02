<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExhibitionImage extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'exhibition_id',
        'url',
        'order'
    ];

    protected $table = 'exhibition_images';

    //===============Relationships==================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
}

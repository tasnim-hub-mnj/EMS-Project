<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booth extends Model
{
    use HasFactory;
    protected $fillable =
    [
        'exhibition_id',
        'number',
        'area',
        'status_inv',
        'status',
        'price',
        'end_date',
        'location',
        'services',
        'map_x',
        'map_y',
        'map_z'
    ];

    protected $table = 'booths';

    protected $casts =
    [
        'area'      => 'float',
        'price'     => 'float',
        'end_date'  => 'date',
    ];

    //---------------------------------------------------
    protected static function booted()
    {
        static::creating(function ($booth)
        {
            // إذا ما كان في end_date، خليه ياخد تاريخ نهاية المعرض
            if (empty($booth->end_date))
            {
                $booth->end_date = $booth->exhibition->end_date;
            }
        });
    }

    // =================Relationships===================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
    public function boothBookings()
    {
        return $this->hasMany(BoothBooking::class);
    }
    //=====================================================
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
    //=====================================================
    public function reviews()
    {
        return $this->hasMany(BoothReview::class);
    }
    //=====================================================
    // public function collectedBooths()
    // {
    //     return $this->hasMany(CollectedBooths::class);
    // }
    //=====================================================

}

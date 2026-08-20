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
        'section_id',
        'section',
        'number',
        'area',
        'status_inv',
        'status',
        'price',
        'location',
        'services',
        'amenities',
        'image',
        'map_x',
        'map_y',
        'map_z',
        'map_width',
        'map_height',
        'description',
        'pricing_type',
    ];

    protected $table = 'booths';

    protected $casts =
    [
        'area'      => 'float',
        'price'     => 'float',
        'services' => 'array',
        'amenities' => 'array',

    ];
    //---------------------------------------------------

    // =================Relationships===================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
    //=====================================================
    public function boothBookings()
    {
        return $this->hasMany(BoothBooking::class)->where('status', 'approved');
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
    public function boothImages()
    {
        return $this->hasMany(BoothImage::class);
    }
    //=====================================================
    // public function collectedBooths()
    // {
    //     return $this->hasMany(CollectedBooths::class);
    // }
    //=====================================================

}

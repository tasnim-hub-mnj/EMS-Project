<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoothProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'booth_id',
        'investor_id',
        'company_nature',
        'services_products',
        'headquarters',
        'social_links',
        'product_images',
        'booth_images',
    ];

    protected $casts = [
        'social_links'   => 'array',
        'product_images' => 'array',
        'booth_images'   => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // ملف الجناح belongsTo الجناح نفسه
    public function booth()
    {
        return $this->belongsTo(Booth::class, 'booth_id');
    }

    // ملف الجناح belongsTo المستثمر الذي يمتلكه
    public function investor()
    {
        return $this->belongsTo(Investor::class, 'investor_id');
    }
}

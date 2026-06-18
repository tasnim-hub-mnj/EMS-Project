<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'investor_id',
        'company_name',
        'about',
        'email',
        'phone',
        'location',
        'website',
        'social_links',
        'logo',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    // كل بروفايل شركة مرتبط بمستثمر واحد
    public function investor()
    {
        return $this->belongsTo(Investor::class, 'investor_id');
    }
}

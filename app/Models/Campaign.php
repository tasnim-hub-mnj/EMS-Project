<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'investor_id',
        'title',
        'description',
        'type',
        'budget',
        'reach',
        'status',
        'start_date',
        'end_date',
        'weekly_trend',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'weekly_trend' => 'array',
    ];

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function company()
    {
        return $this->belongsTo(CompanyProfile::class);
    }
}


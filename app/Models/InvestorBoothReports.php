<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorBoothReports extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts =
    [
        'data_graph' => 'array',
        'data_specific_table' => 'array',
        'data_recommendations' => 'array',
        'performance_index' => 'float',
        'growth_rate' => 'float',
    ];

    protected $table = 'investor_booth_reports';

    //===============Relationships==================
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=================================================
    public function boothBooking()
    {
        return $this->belongsTo(BoothBooking::class);
    }
    //=================================================

}

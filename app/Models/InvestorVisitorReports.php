<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorVisitorReports extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts =
    [
        'peak_hours' => 'array',
        'data_graph' => 'array',
        'data_specific_table' => 'array',
        'data_recommendations' => 'array',
        'growth_rate' => 'float',
        'average_visitors_per_hour' => 'float',
    ];

    protected $table = 'investor_visitor_reports';

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

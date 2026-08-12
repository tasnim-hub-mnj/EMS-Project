<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorSponsorshipsReports extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts =
    [
        'data_graph' => 'array',
        'data_specific_table' => 'array',
        'data_recommendations' => 'array',
        'growth_rate' => 'float',
        'total_amount' => 'float',
        'overall_ctr' => 'float',

        'total_campaigns' => 'integer',
        'total_reach' => 'integer',
        'total_favorites' => 'integer',
    ];

    protected $table = 'investor_sponsorships_reports';

    //===============Relationships==================
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=================================================
}

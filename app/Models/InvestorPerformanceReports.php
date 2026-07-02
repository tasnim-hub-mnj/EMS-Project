<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorPerformanceReports extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'investor_id',
        'total_booths',
        'total_visitors',
        'total_potential_clients',
        'total_conversions',
        'avg_performance_index',
    ];

    protected $table = 'investor_performance_reports';

    //===============Relationships==================
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=================================================
}

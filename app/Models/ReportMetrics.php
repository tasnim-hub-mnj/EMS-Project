<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportMetrics extends Model
{
     use HasFactory;

    protected $fillable =
    [
        'report_id',
        'key',
        'label',
        'value',
        'trend',
        'sparkline_data'
    ];

    protected $casts =
        [
            'sparkline_data' => 'array',
        ];

    protected $table = 'report_metrics';

    //===============Relationships==================
    public function report()
    {
        return $this->belongsTo(ReportInvestor::class, 'report_id');
    }
    //=================================================
}

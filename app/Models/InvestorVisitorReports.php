<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorVisitorReports extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'investor_id',
        'booth_booking_id',
        'date',
        'total_visitors',
        'new_visitors',
        'avg_visit_time',
        'peak_hour',
        'repeat_rate',
        'growth_rate',
    ];

    protected $table = 'investor_visitor_reports';

    //===============Relationships==================
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{

    protected $fillable = [
        'id',
        'investor_id',
        'title',
        'type',
        'description',
        'period',
        'booth_name',
        'exhibition_name',
        'main_value',
        'main_label',
        'trend',
        'sparkline_data',
    ];

    protected $casts = [
        'sparkline_data' => 'array',
    ];
    // =================Relationships===================
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    //=====================================================
    //بس المستثمر مو هو يلي بينشئ التقارير يتم انشاء التقارير من النظام بشكل تلقائي اوكي؟
    //=====================================================
}


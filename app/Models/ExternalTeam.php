<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalTeam extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'exhibition_id',
        'name',
        'company',
        'role',
        'description',
        'offical_name',
        'email',
        'phone',
        'amount',
        'start_date',
        'end_date',
        'classification',
        'notes',
        'status'
    ];

    protected $table = 'external_teams';

    //===============Relationships==================

    
}

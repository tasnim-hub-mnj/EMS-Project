<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalTeamTask extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'title',
        'external_teams_id',
        'external_team_member_id',
        'due_date',
        'status',
    ];

    protected $table = 'external_team_tasks';

    //===============Relationships==================
}

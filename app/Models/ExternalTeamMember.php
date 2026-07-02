<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalTeamMember extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'external_teams_id',
        'name',
        'role',
        'phone',
        'email',
    ];

    protected $table = 'external_team_members';

    //===============Relationships==================
    public function externalTeam()
    {
        return $this->belongsTo(ExternalTeam::class);
    }
    //=====================================================

}

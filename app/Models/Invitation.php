<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'sponsorEvent_id',
        'name',
        'email',
        'phone',
        'method_send',
        'status',
    ];

    protected $table = 'invitations';

    //===============Relationships==================
}

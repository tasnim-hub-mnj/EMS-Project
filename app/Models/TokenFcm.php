<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token_fcm extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'token_fcm'
    ];
    
    protected $table = 'token_fcms';
}

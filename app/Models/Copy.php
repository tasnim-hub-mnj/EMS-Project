<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Copy extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'exhibition_id',
        'year',
        'start_date',
        'end_date',
        'status'
    ];

    protected $table = 'copies';

    //===============Relationships==================
}

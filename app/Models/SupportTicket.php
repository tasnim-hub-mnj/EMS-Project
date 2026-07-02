<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'type',
        'body',
        //'latitude',
        //'longitude',
        'status'
    ];
    
    protected $table = 'support_tickets';

    //===============Relationships==================

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

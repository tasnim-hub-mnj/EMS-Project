<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
      protected $fillable = [
        'user_id',
        'type',
        'body',
        'latitude',
        'longitude',
        'status'
           ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

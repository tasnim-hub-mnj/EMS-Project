<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable =
    [
        'user_id',
        'code',
        'expires_at',
        'is_used'
    ];

    protected $table = 'otp_codes';

    protected $casts =
    [
        'expires_at' => 'datetime',
        'is_used' => 'boolean'
    ];
    // =================Relationships===================
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable =
        [
            'id',
            'user_id',
            'title',
            'body',
            'type',
            'read',
            'data',
            'action_url',
        ];

    protected $casts =
        [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'read' => 'boolean',
            'data' => 'array',
        ];

    //============================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    //============================================
}

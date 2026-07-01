<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'exhibition_id',
        'last_message',
        'unread_count'
    ];

    protected $table = 'conversations';

    //===============Relationships==================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'conv_id');
    }
}

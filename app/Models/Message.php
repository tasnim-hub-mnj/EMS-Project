<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conv_id', 'sender_id', 'body', 'is_read'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conv_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

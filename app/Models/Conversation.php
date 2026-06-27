<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['user_id', 'exhibition_id', 'last_message', 'unread_count'];

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

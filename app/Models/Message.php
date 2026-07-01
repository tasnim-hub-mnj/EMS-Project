<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'conv_id',
        'sender_id',
        'body',
        'is_read'
    ];

    protected $table = 'messages';

    //===============Relationships==================


    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conv_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

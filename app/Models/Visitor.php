<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function schedule()
    {
        return $this->hasMany(VisitorSchedule::class);
    }

    public function collectedBooths()
    {
        return $this->hasMany(CollectedBooths::class);
    }
    //المفضلة كمان والتقييمات
    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }


}

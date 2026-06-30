<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'profession',
        'city',
        'country',
        'interests',
        'preferred_language',
    ];

    protected $casts = [
        'interests' => 'array',
    ];
    //==========================================
    //الزائر له

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    // public function conversations()
    // {
    //     return $this->hasMany(Conversation::class);
    // }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function schedule()
    {
        return $this->hasMany(VisitorSchedule::class);
    }
    //===========================================
    // الزائر له اجنحة محفوظة

    public function collectedBooths()
    {
        return $this->hasMany(CollectedBooths::class);
    }
    //============================================
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    //التقييمات
    //============================================

    //الزائر له تذاكر دعم
    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }


}

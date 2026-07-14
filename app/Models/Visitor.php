<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable =
        [
            'user_id',
            'first_name',
            'last_name',
            'profession',
            'city',
            'hobby',
            'interests',
            'avatar_url',
        ];

    protected $table = 'visitors';

    protected $casts =
        [
            'interests' => 'array',
        ];

    // =================Relationships===================
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //===================================================
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'visitor_id');
    }
    //===================================================
    public function eventTickets()
    {
        return $this->hasMany(EventTicket::class, 'visitor_id');
    }
    //===================================================


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
    //=============================================
    public function boothReviews()
    {
        return $this->hasMany(BoothReview::class);
    }
    //=============================================
    public function exhibitionReviews()
    {
        return $this->hasMany(ExhibitionReview::class);
    }
    //=============================================

    public function sponsorEventTickets()
    {
        return $this->hasMany(SponserEventTicket::class);
    }






}

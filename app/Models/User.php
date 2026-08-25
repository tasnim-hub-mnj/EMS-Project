<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Override;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable =
        [
            'email',
            'phone',
            'password',
            'role',
            'status',
            'is_verified',
            'notifications_enabled',
            'favorites_notify',
            'reports_notify',
        ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notifications_enabled' => 'boolean',
            'favorites_notify' => 'boolean',
            'reports_notify' => 'boolean',
        ];
    }
    //------------------------------------------------
    public function routeNotificationForFcm()
    {
        //fcm_token
        return $this->fcm_token;
    }

    //=================Relationships===================
    public function organizer()
    {
        return $this->hasOne(Organizer::class, 'user_id');
    }
    //=====================================================
    public function investor()
    {
        return $this->hasOne(Investor::class, 'user_id');
    }
    //=====================================================
    public function visitor()
    {
        return $this->hasOne(Visitor::class, 'user_id');
    }
    //=====================================================
    public function staff()
    {
        return $this->hasOne(StaffMember::class, 'user_id');
    }
    //=====================================================
    public function favorites()//v-i
    {
        return $this->hasMany(Favorite::class, 'user_id');
    }
    //=====================================================
    public function exhibitionReviews()//v
    {
        return $this->hasMany(ExhibitionReview::class, 'user_id');
    }
    //=====================================================
    public function boothReviews()//v
    {
        return $this->hasMany(BoothReview::class, 'user_id');
    }
    //=====================================================
    public function map()//o
    {
        return $this->hasOne(Map::class);
    }
    //=====================================================
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    //=====================================================
    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }
    //=====================================================
    public function fcmToken()
    {
        return $this->hasOne(FcmToken::class);
    }
    //=====================================================
    public function firebaseSync()
    {
        return $this->hasOne(FirebaseSync::class);
    }
    //=====================================================

}


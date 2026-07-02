<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory,Notifiable,HasApiTokens;

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
        'token_fcm',
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
        ];
    }
    //------------------------------------------------
    public function routeNotificationForFcm()
    {
        //fcm_token
        return $this->fcm_token;
    }

    // =================Relationships===================
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
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'user_id');
    }
    //=====================================================
    public function exhibitionReviews()
    {
        return $this->hasMany(ExhibitionReview::class, 'user_id');
    }
    //=====================================================
    public function boothReviews()
    {
        return $this->hasMany(BoothReview::class, 'user_id');
    }
    
    //=====================================================
    //=====================HANAN===========================
    //=====================================================

    public function schecules()
    {
        return $this->hasMany(VisitorSchedule::class);
    }
    //=====================================================
     public function collectedBooths()
    {
        return $this->hasMany(CollectedBooths::class);
    }
    //=====================================================
     public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }
    //=====================================================


}


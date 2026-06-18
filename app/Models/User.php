<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',

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
    //=====================================================
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
    public function exhibitions()
    {
        return $this->hasMany(Exhibition::class, 'organizer_id');
    }
    //=====================================================
    public function boothBookings()
    {
        return $this->hasMany(BoothBooking::class, 'investor_id');
    }
    //=====================================================
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }
    //=====================================================
    public function events()
    {
        return $this->hasMany(Event::class, 'investor_id');
    }

    public function sponsroEvents()
    {
        return $this->hasMany(SponsorEvent::class, 'investor_id');
    }

    public function sponsorshipBookings()
    {
        return $this->hasMany(SponsorshipBooking::class, 'investor_id');
    }
    //=====================================================
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'user_id');
    }

    public function exhibitionReviews()
    {
        return $this->hasMany(ExhibitionReview::class, 'user_id');
    }

    public function boothReviews()
    {
        return $this->hasMany(BoothReview::class, 'user_id');
    }

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
    

}


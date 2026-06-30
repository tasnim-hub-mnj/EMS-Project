<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'trade_name',
        'website',
        'activity_type',
        'avatar_url',
        'bio',
        'location',
        'logo',
        'terms_accepted',
        'status',
        'social_links',
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'social_links' => 'array',
    ];


    // =================Relationships===================
    // كل مستثمر يرتبط بحساب مستخدم واحد
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //=====================================================
    // المستثمر يمكنه حجز عدة أجنحة
    public function boothBookings()
    {
        return $this->hasMany(BoothBooking::class);
    }
    //=====================================================
    // // المستثمر يملك قائمة مفضلات
    // public function favorites()
    // {
    //     return $this->hasMany(Favorite::class, 'investor_id');
    // }
    //=====================================================
    // المستثمر لديه طلبات حجز رعاية متعددة
    public function sponsorshipBookings()
    {
        return $this->hasMany(SponsorshipBooking::class);
    }
    //=====================================================
    // التقارير المرتبطة بالمستثمر
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
    //=====================================================
    // الملف التجاري للمستثمر
    public function companyProfile()
    {
        return $this->hasOne(CompanyProfile::class, 'investor_id');
    }
    //=====================================================
    // الفعاليات التي أنشأها المستثمر
    public function events()
    {
        return $this->hasMany(Event::class);
    }
    //=====================================================
}

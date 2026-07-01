<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exhibition extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'organizer_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'location',
        'description',
        'city',
        'status',
        'copy_status',
        'available_booths',
        'total_booths',
        'total_sponser_events',
        'visitors_count',
        'sectors',
        'extra_services',
        'working_hours',
        'is_paid',
    ];

    protected $table = 'exhibitions';

    protected $casts =
    [
        'sectors'        => 'array',
        'extra_services' => 'array',
        'start_date'     => 'date',
        'end_date'       => 'date',
    ];
    
    //===============Relationships==================
    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
    //=====================================================
    // المعرض يحتوي على عدة أجنحة
    public function booths()
    {
        return $this->hasMany(Booth::class);
    }
    //=====================================================
    // المعرض يحتوي على فعاليات إعلانية متعلقة به
    public function sponsorEvents()
    {
        return $this->hasMany(SponsorEvent::class);
    }
    //=====================================================
    // المعرض قد يحتوي على طلبات تذاكر عبر الأحداث والأجنحة
    public function tickets()
    {
        return $this->hasManyThrough(Ticket::class, Booth::class);
    }
    //=====================================================
    // يمكن للمستثمر إضافة المعرض إلى المفضلة
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
    //=====================================================
    // الصور المرتبطة بالمعرض إن وجدت
    public function images()
    {
        return $this->hasMany(ExhibitionImage::class);
    }
    //=====================================================
    // التقييمات التي يضعها المستخدمون على المعرض
    public function reviews()
    {
        return $this->hasMany(ExhibitionImage::class);
    }
    //=====================================================
}





<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoothReview extends Model
{
    protected $fillable = [
        'booth_id',
        'user_id',
        'rating',
        'comment',
    ];

    // التقييم تابع لكشك واحد

    protected $table = 'booth_reviews';
    //===============Relationships==================
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //=================================================
    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }
    //=================================================
}

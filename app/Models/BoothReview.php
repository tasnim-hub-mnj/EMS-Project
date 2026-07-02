<?php

namespace App\Models;

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
    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

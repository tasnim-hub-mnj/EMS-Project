<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExhibitionReview extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'exhibition_id',
        'rating',
        'comment'
    ];

    protected $table = 'exhibition_reviews';

    //===============Relationships==================
    public function visitor()
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }
    //=====================================================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
    //=====================================================
}

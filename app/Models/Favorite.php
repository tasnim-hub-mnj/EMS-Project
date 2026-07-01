<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'favoritable_id',
        'favoritable_type',
    ];

    protected $table = 'favorites';

    //===============Relationships==================

    public function favoritable()
    {
        return $this->morphTo();
    }
    //=============================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

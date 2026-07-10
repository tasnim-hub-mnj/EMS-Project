<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'company_name',
        'category',
        'headquarters',
        'reg_number',
        'location',
        'logo',
        'file',
        'description',
    ];

    protected $table = 'organizers';

    // =================Relationships===================
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //===================================================
    public function exhibition()
    {
        return $this->hasOne(Exhibition::class, 'organizer_id');
    }
    //=====================================================
}

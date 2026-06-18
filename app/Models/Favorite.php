<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'favoritable_id',
        'favoritable_type',
    ];
// =================Relationships===================
    public function favoritable()
    {
        return $this->morphTo();
    }
//=============================================
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
}

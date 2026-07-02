<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectedBooths extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'booth_id',
        'qr_data',
        'scanned_at'
    ];

    protected $table = 'collected_booths';

    //===============Relationships==================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }
}

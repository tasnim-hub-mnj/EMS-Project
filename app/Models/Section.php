<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $table = 'sections';

    protected $fillable = [
        'exhibition_id',
        'name',
        'type',
        'width',
        'height',
        'map_x',
        'map_y',
        'metadata',
    ];

    protected $casts = [
        'width' => 'float',
        'height' => 'float',
        'metadata' => 'array',
    ];

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function booths()
    {
        return $this->hasMany(Booth::class, 'section_id');
    }
}

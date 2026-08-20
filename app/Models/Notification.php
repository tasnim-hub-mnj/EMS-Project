<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable =
    [
        'id',
        'user_id',
        'exhibition_id',
        'portal_link_id',
        'title',
        'body',
        'type',
        'permission_key',
        'read',
        'data',
        'action_url',
    ];

    protected $casts =
    [
        'read' => 'boolean',
        'data' => 'array',
    ];

    //============================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function portalLink()
    {
        return $this->belongsTo(PortalLink::class);
    }
    //============================================
}

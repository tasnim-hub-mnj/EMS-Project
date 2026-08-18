<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PortalLink extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'portal_links';

    protected $casts =
    [
        'permissions' => 'array',
        'messaging_channels' => 'array',
        'is_manager' => 'boolean',
        'active' => 'boolean',
    ];

    //================= Relationships ===================
    public function staff()
    {
        return $this->belongsTo(StaffMember::class, 'staff_id');
    }
    //=====================================================
    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class, 'exhibition_id');
    }
    //=====================================================
    // رابط الأب (لو كان هذا رابط فرعي)
    public function parent()
    {
        return $this->belongsTo(PortalLink::class, 'parent_token', 'token');
    }
    //=====================================================
    // الروابط الفرعية التابعة لهذا الرابط
    public function children()
    {
        return $this->hasMany(PortalLink::class, 'parent_token', 'token');
    }
    //=====================================================
}

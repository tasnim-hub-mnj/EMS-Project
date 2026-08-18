<?php

namespace App\Http\Controllers;

use App\Models\PortalLink;
use App\Models\StaffMember;
use Illuminate\Support\Str;

use App\Http\Requests\StorePortalLinkRequest;
// use App\Http\Requests\UpdatePortalLinkRequest;
use App\Http\Resources\PortalLinkResource;

class PortalLinkController extends Controller
{
    public function index()//فلترة حسب: created_by, parent_token, token
    {
        $query = PortalLink::query();

        if (request('created_by')) 
        {
            $query->where('created_by', request('created_by'));
        }

        if (request('parent_token')) 
        {
            $query->where('parent_token', request('parent_token'));
        }

        if (request('token')) 
        {
            $query->where('token', request('token'));
        }

        $links = $query->orderByDesc('id')->get();

        return PortalLinkResource::collection($links);
    }
    //================================================================
    public function store(StorePortalLinkRequest $request)//إنشاء رابط وصول جديد
    {
        $data = $request->validated();

        // توليد UUID للرابط
        $data['token'] = Str::uuid()->toString();

        // توليد QR
        $data['qr_value'] = "STAFF:" . $data['token'];

        // حالة الرابط
        $data['active'] = true;

        $link = PortalLink::create($data);

        return new PortalLinkResource($link);
    }
    //================================================================
    public function show($token)
    {
        $link = PortalLink::where('token', $token)->firstOrFail();

        return new PortalLinkResource($link);
    }
    //================================================================
    public function deactivate($token)//تعطيل الرابط
    {
        $link = PortalLink::where('token', $token)->firstOrFail();

        $link->update([
            'active' => false
        ]);

        return new PortalLinkResource($link);
    }
    //================================================================
    public function destroy($token)//حذف الرابط نهائيًا
    {
        $link = PortalLink::where('token', $token)->firstOrFail();

        $link->delete();

        return response()->json([
            'success' => true,
            'message' => 'Portal link deleted successfully'
        ], 200);
    }
    //================================================================
}

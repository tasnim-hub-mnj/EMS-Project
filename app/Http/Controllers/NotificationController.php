<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\PortalLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
   public function index(Request $request)

    {
       $query = $this->scopedQuery($request);
       $notifications = $query
            ->orderByDesc('created_at')
            ->paginate(request('per_page', 20));

        return NotificationResource::collection($notifications);
    }
    //===============================================================
    public function unread(Request $request)
    {
        $notifications = $this->scopedQuery($request)
            ->where('read', false)
            ->orderByDesc('created_at')
            ->paginate(request('per_page', 20));

        return NotificationResource::collection($notifications);
    }

    private function scopedQuery(Request $request)
    {
        $query = Notification::where('user_id', Auth::id());

        $portalToken = $request->input('portal_token') ?: $request->header('X-Portal-Token');

        if ($portalToken) {
            $link = PortalLink::where('token', $portalToken)
                ->where('active', true)
                ->whereHas('staff', fn ($staff) => $staff->where('user_id', Auth::id()))
                ->first();

            // Do not reveal whether another user's portal link exists.
            // An unrelated or stale portal session simply has no notifications.
            if (!$link) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(function ($scoped) use ($link) {
                $scoped->where('portal_link_id', $link->id)
                    ->orWhere(function ($direct) use ($link) {
                        $direct->whereNull('portal_link_id')
                            ->where('exhibition_id', $link->exhibition_id)
                            ->whereIn('permission_key', $link->permissions ?? []);
                    });
            });
        }

        return $request->filled('exhibition_id')
            ? $query->where('exhibition_id', $request->integer('exhibition_id'))
            : $query;
    }
    //===============================================================
    public function markRead($id)
    {
        Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read' => true]);

        return ['success' => true];
    }
    //===============================================================
    public function markAllRead()
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);

        return [
            'success' => true,
            'count' => $count
        ];
    }

    public function preferences()
    {
        $user = Auth::user();

        return response()->json([
            'data' => [
                'notifications_enabled' => (bool) $user->notifications_enabled,
                'favorites_notify' => (bool) $user->favorites_notify,
                'reports_notify' => (bool) $user->reports_notify,
            ],
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            'notifications_enabled' => ['sometimes', 'boolean'],
            'favorites_notify' => ['sometimes', 'boolean'],
            'reports_notify' => ['sometimes', 'boolean'],
        ]);

        Auth::user()->update($data);

        return $this->preferences();
    }
    //===============================================================
    public function destroy($id)
    {
        Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return ['success' => true];
    }
    //===============================================================
}

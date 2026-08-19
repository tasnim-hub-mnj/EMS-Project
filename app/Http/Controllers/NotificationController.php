<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(request('per_page', 20));

        return NotificationResource::collection($notifications);
    }
    //===============================================================
    public function unread()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->orderByDesc('created_at')
            ->paginate(request('per_page', 20));

        return NotificationResource::collection($notifications);
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

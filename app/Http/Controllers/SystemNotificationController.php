<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemNotificationController extends Controller
{
    public function fetch()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['count' => 0, 'notifications' => []]);
        }

        $count = \App\Models\SystemNotification::getUnreadCount($user->id);
        $notifications = \App\Models\SystemNotification::getRecent($user->id, 5); // Latest 5

        return response()->json([
            'count' => $count,
            'notifications' => $notifications
        ]);
    }

    public function index()
    {
        $notifications = \App\Models\SystemNotification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin_panel.notifications.index', compact('notifications'));
    }
}

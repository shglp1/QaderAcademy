<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\BroadcastNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Broadcast a notification to all users or segmented by role
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target' => 'required|in:all,students,trainers'
        ]);

        $query = User::query();
        
        if ($request->target === 'students') {
            $query->where('role', 'student');
        } elseif ($request->target === 'trainers') {
            $query->where('role', 'trainer');
        }

        $users = $query->get();
        
        $notification = new BroadcastNotification(
            $request->title,
            $request->message,
            $request->target
        );

        foreach ($users as $user) {
            $user->notify($notification);
        }

        return response()->json([
            'message' => 'Notification broadcast successfully',
            'recipients_count' => $users->count()
        ]);
    }
}

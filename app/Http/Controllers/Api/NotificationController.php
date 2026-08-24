<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($notifications);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();
        
        $count = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
        
        return response()->json(['unread_count' => $count]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        
        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification,
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        
        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        
        $notification->delete();
        
        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    public function getPreferences(Request $request)
    {
        $user = $request->user();
        
        $preferences = NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'task_assignment' => true,
                'status_change' => true,
                'mention' => true,
                'deadline_reminder' => true,
                'overdue' => true,
                'email_notifications' => false,
            ]
        );
        
        return response()->json($preferences);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'task_assignment' => 'sometimes|boolean',
            'status_change' => 'sometimes|boolean',
            'mention' => 'sometimes|boolean',
            'deadline_reminder' => 'sometimes|boolean',
            'overdue' => 'sometimes|boolean',
            'email_notifications' => 'sometimes|boolean',
        ]);
        
        $preferences = NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'task_assignment' => true,
                'status_change' => true,
                'mention' => true,
                'deadline_reminder' => true,
                'overdue' => true,
                'email_notifications' => false,
            ]
        );
        
        $preferences->update($validated);
        
        return response()->json([
            'message' => 'Preferences updated successfully',
            'preferences' => $preferences,
        ]);
    }

    public static function create($userId, $type, $title, $message, $data = null)
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ? json_encode($data) : null,
            'is_read' => false,
        ]);
    }
}
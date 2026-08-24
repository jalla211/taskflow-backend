<?php

namespace App\Listeners;

use App\Events\UserMentioned;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendMentionNotification
{
    public function handle(UserMentioned $event)
    {
        Log::info('=== MENTION LISTENER TRIGGERED ===');
        Log::info('Mentioned User ID: ' . $event->mentionedUser->id);
        Log::info('Mentioned By: ' . $event->mentionedBy->name);

        $preference = $event->mentionedUser->notificationPreference;
        if ($preference && !$preference->mention) {
            Log::info('⚠️ User disabled mention notifications');
            return;
        }

        Notification::create([
            'user_id' => $event->mentionedUser->id,
            'type' => 'mention',
            'title' => 'You were mentioned',
            'message' => "{$event->mentionedBy->name} mentioned you in a comment on task: {$event->comment->task->title}",
            'data' => json_encode([
                'task_id' => $event->comment->task_id,
                'comment_id' => $event->comment->id,
                'mentioned_by' => $event->mentionedBy->id,
            ]),
            'is_read' => false,
        ]);

        Log::info('✅ Mention notification created for ' . $event->mentionedUser->name);
    }
}
<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendTaskAssignmentNotification
{
    public function handle(TaskAssigned $event)
    {
        Log::info('=== LISTENER TRIGGERED ===');
        Log::info('Task ID: ' . $event->task->id);
        Log::info('Assigned To User ID: ' . $event->assignedTo->id);
        Log::info('Assigned By User ID: ' . $event->assignedBy->id);

        // Check if the assigned user wants this notification
        $preference = $event->assignedTo->notificationPreference;
        Log::info('Notification Preference: ' . ($preference ? 'Found' : 'Not Found'));

        if ($preference && !$preference->task_assignment) {
            Log::info('User disabled task assignment notifications');
            return;
        }

        try {
            $notification = Notification::create([
                'user_id' => $event->assignedTo->id,
                'type' => 'task_assignment',
                'title' => 'New Task Assigned',
                'message' => "{$event->assignedBy->name} assigned you the task: {$event->task->title}",
                'data' => json_encode([
                    'task_id' => $event->task->id,
                    'project_id' => $event->task->project_id,
                    'assigned_by' => $event->assignedBy->id,
                ]),
                'is_read' => false,
            ]);

            Log::info('✅ Notification created! ID: ' . $notification->id);
        } catch (\Exception $e) {
            Log::error('❌ Failed to create notification: ' . $e->getMessage());
        }
    }
}